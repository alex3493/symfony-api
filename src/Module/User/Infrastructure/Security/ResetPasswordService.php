<?php
declare(strict_types=1);

namespace App\Module\User\Infrastructure\Security;

use App\Module\Shared\Domain\Exception\FormValidationException;
use App\Module\Shared\Domain\Exception\NotFoundDomainException;
use App\Module\User\Domain\Contract\ResetPasswordServiceInterface;
use App\Module\User\Domain\ResetPasswordToken;
use App\Module\User\Domain\User;
use App\Module\User\Infrastructure\Persistence\Doctrine\ResetPasswordTokenRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

readonly class ResetPasswordService implements ResetPasswordServiceInterface
{
    /**
     * @param \App\Module\User\Infrastructure\Persistence\Doctrine\ResetPasswordTokenRepository $repository
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface $params
     */
    public function __construct(
        private ResetPasswordTokenRepository $repository, private UserPasswordHasherInterface $passwordHasher, private MailerInterface $mailer,
        private ContainerBagInterface $params
    ) {
    }

    /**
     * @param \App\Module\User\Domain\ResetPasswordToken $resetToken
     * @return void
     */
    public function delete(ResetPasswordToken $resetToken): void
    {
        $this->repository->delete($resetToken);
    }

    /**
     * @param string $token
     * @param string $email
     * @param string $password
     * @return \App\Module\User\Domain\User
     * @throws \App\Module\Shared\Domain\Exception\FormValidationException
     * @throws \App\Module\Shared\Domain\Exception\NotFoundDomainException
     * @throws \Doctrine\ORM\Exception\NotSupported
     */
    public function resetPassword(string $token, string $email, string $password): User
    {
        $token = $this->repository->findByToken($token);

        if (is_null($token) || $token->getEmail() !== $email) {
            throw new FormValidationException('Password reset token is invalid or wrong email provided.', [
                [
                    'property' => 'email',
                    'errors' => ['Provided email is invalid or reset token not found'],
                    'context' => 'User',
                ],
            ]);
        }

        if (! $token->isValid()) {
            throw new FormValidationException('Password reset token is invalid.', [
                [
                    'property' => 'email',
                    'errors' => ['Password reset token expired'],
                    'context' => 'User',
                ],
            ]);
        }

        $userRepository = $this->repository->getRelatedRepository(User::class);

        $user = $userRepository->findOneBy(['email' => $email]);

        if (is_null($user)) {
            throw new NotFoundDomainException('User not found');
        }

        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $password);
        $user->setPassword($hashedPassword);

        $userRepository->save($user);

        // Remove token after successful single use.
        $this->delete($token);

        return $user;
    }

    /**
     * @param string $email
     * @return void
     * @throws \Doctrine\ORM\Exception\NotSupported
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Random\RandomException
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    public function generateResetPasswordToken(string $email): void
    {
        $userRepository = $this->repository->getRelatedRepository(User::class);
        $user = $userRepository->findOneBy(['email' => $email]);

        // Only proceed if email provided belongs to a valid user. Soft-deleted users cannot request password reset.
        if (is_null($user)) {
            return;
        }

        $token = bin2hex(random_bytes(32));

        $expiresAfter = $this->params->get('app.reset_password_token_expiration_minutes');

        $validUntil = $expiresAfter > 0 ? (new \DateTime())->add(new \DateInterval("PT{$expiresAfter}M")) : null;

        $resetToken = new ResetPasswordToken($email, $token, $validUntil);

        // We allow only one password reset token per email (user).
        if ($existing = $this->repository->findByUser($email)) {
            $this->repository->delete($existing);
        }

        $this->repository->save($resetToken);

        $this->sendResetPasswordEmail($resetToken);
    }

    /**
     * @param \App\Module\User\Domain\ResetPasswordToken $resetToken
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Symfony\Component\Mailer\Exception\TransportExceptionInterface
     */
    private function sendResetPasswordEmail(ResetPasswordToken $resetToken): void
    {
        $emailFrom = $this->params->get('app.email_from');

        $email = ((new TemplatedEmail()))->from($emailFrom)->to($resetToken->getEmail())->subject('Password reset link')
                                         ->htmlTemplate('email/emailResetPassword.html.twig')->context([
                'resetToken' => $resetToken->getResetToken(),
                'userEmail' => $resetToken->getEmail(),
            ]);

        $this->mailer->send($email);
    }
}
