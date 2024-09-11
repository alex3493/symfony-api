<?php
declare(strict_types=1);

namespace App\Command\AddUser;

use App\Module\User\Domain\User;
use App\Module\User\Domain\ValueObject\UserRole;
use App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository;
use App\Module\User\Infrastructure\Security\AuthUser;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Stopwatch\Stopwatch;
use function Symfony\Component\String\u;

/**
 * A console command that creates users and stores them in the database.
 *
 * Based on https://github.com/symfony/demo/blob/main/src/Command/AddUserCommand.php
 *
 * Copyright (c) 2015-present Fabien Potencier
 */
#[AsCommand(name: 'app:add-user', description: 'Creates users and stores them in the database')]
final class AddUserCommand extends Command
{
    private SymfonyStyle $io;

    /**
     * @param \Doctrine\ORM\EntityManagerInterface $entityManager
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Command\AddUser\Validator $validator
     * @param \App\Module\User\Infrastructure\Persistence\Doctrine\UserRepository $users
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher, private readonly Validator $validator,
        private readonly UserRepository $users
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setHelp($this->getCommandHelp())
            // commands can optionally define arguments and/or options (mandatory and optional)
            // see https://symfony.com/doc/current/components/console/console_arguments.html
             ->addArgument('email', InputArgument::OPTIONAL, 'The email of the new user')
             ->addArgument('password', InputArgument::OPTIONAL, 'The plain password of the new user')
             ->addArgument('first-name', InputArgument::OPTIONAL, 'The first name of the new user')
             ->addArgument('last-name', InputArgument::OPTIONAL, 'The last name of the new user')
             ->addOption('admin', null, InputOption::VALUE_NONE, 'If set, the user is created as an administrator');
    }

    /**
     * This optional method is the first one executed for a command after configure()
     * and is useful to initialize properties based on the input arguments and options.
     */
    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // SymfonyStyle is an optional feature that Symfony provides, so you can
        // apply a consistent look to the commands of your application.
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        if (null !== $input->getArgument('email') && null !== $input->getArgument('password') && null !== $input->getArgument('first-name') && null !== $input->getArgument('last-name')) {
            return;
        }

        $this->io->title('Add User Command Interactive Wizard');
        $this->io->text([
            'If you prefer to not use this interactive wizard, provide the',
            'arguments required by this command as follows:',
            '',
            ' $ php bin/console app:add-user email@example.com password first-name last-name [--admin]',
            '',
            'Now we\'ll ask you for the value of all the missing command arguments.',
        ]);

        // Ask for the email if it's not defined.
        /** @var string|null $email */
        $email = $input->getArgument('email');

        if (null !== $email) {
            $this->io->text(' > <info>Email</info>: '.$email);
        } else {
            $email = $this->io->ask('Email', null, $this->validator->validateEmail(...));
            $input->setArgument('email', $email);
        }

        // Ask for the password if it's not defined.
        /** @var string|null $password */
        $password = $input->getArgument('password');

        if (null !== $password) {
            $this->io->text(' > <info>Password</info>: '.u('*')->repeat(u($password)->length()));
        } else {
            $password = $this->io->askHidden('Password (your type will be hidden)',
                $this->validator->validatePassword(...));
            $input->setArgument('password', $password);
        }

        // Ask for the first name if it's not defined.
        /** @var string|null $firstName */
        $firstName = $input->getArgument('first-name');

        if (null !== $firstName) {
            $this->io->text(' > <info>First Name</info>: '.$firstName);
        } else {
            $firstName = $this->io->ask('First Name', null, $this->validator->validateName(...));
            $input->setArgument('first-name', $firstName);
        }

        // Ask for the last name if it's not defined.
        /** @var string|null $lastName */
        $lastName = $input->getArgument('last-name');

        if (null !== $lastName) {
            $this->io->text(' > <info>Last Name</info>: '.$lastName);
        } else {
            $lastName = $this->io->ask('Last Name', null, $this->validator->validateName(...));
            $input->setArgument('last-name', $lastName);
        }

        // Ask for "admin" option if it's not defined.
        /** @var bool $isAdmin */
        $isAdmin = $input->getOption('admin');

        if (true === $isAdmin) {
            $this->io->text(' > <info>Is Admin</info>: yes');
        } else {
            $helper = $this->getHelper('question');
            $question = new ChoiceQuestion('Admin user?', ['No', 'yes'], 'No');
            $isAdmin = $helper->ask($input, $this->io, $question);
            $input->setOption('admin', 'yes' === $isAdmin);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start('add-user-command');

        /** @var string $plainPassword */
        $plainPassword = $input->getArgument('password');

        /** @var string $email */
        $email = $input->getArgument('email');

        /** @var string $firstName */
        $firstName = $input->getArgument('first-name');

        /** @var string $lastName */
        $lastName = $input->getArgument('last-name');

        $isAdmin = $input->getOption('admin');

        $this->validateUserData($plainPassword, $email, $firstName, $lastName);

        try {
            // Create the user.
            $user = User::create($email, $plainPassword, $firstName, $lastName);

            $user->setRoles([$isAdmin ? UserRole::ROLE_ADMIN : UserRole::ROLE_USER]);
        } catch (Exception $e) {
            $this->io->error($e->getMessage());

            return Command::FAILURE;
        }

        // Init auth user and hash user password.
        $authUser = new AuthUser($user);

        $hashedPassword = $this->passwordHasher->hashPassword($authUser, $plainPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->io->success(sprintf('%s was successfully created: %s', $isAdmin ? 'Administrator user' : 'User',
            $user->getEmail()));

        $event = $stopwatch->stop('add-user-command');

        if ($output->isVerbose()) {
            $this->io->comment(sprintf('New user database id: %s / Elapsed time: %.2f ms / Consumed memory: %.2f MB',
                $user->getId(), $event->getDuration(), $event->getMemory() / (1024 ** 2)));
        }

        return Command::SUCCESS;
    }

    private function validateUserData(string $plainPassword, string $email, string $firstName, string $lastName): void
    {
        $existingUser = $this->users->findOneBy(['email' => $email]);

        if (null !== $existingUser) {
            throw new RuntimeException(sprintf('There is already a user registered with the "%s" email.', $email));
        }

        // Validate password and email if is not this input means interactive.
        $this->validator->validatePassword($plainPassword);
        $this->validator->validateEmail($email);
        $this->validator->validateName($firstName);
        $this->validator->validateName($lastName);
    }

    /**
     * The command help is usually included in the configure() method, but when
     * it's too long, it's better to define a separate method to maintain the
     * code readability.
     */
    private function getCommandHelp(): string
    {
        return <<<'HELP'
            The <info>%command.name%</info> command creates new users and saves them in the database:

              <info>php %command.full_name%</info> <comment>email password first_name last_name</comment>

            By default the command creates regular users. To create administrator users,
            add the <comment>--admin</comment> option:

              <info>php %command.full_name%</info> email password first_name last_name <comment>--admin</comment>

            If you omit any of the four required arguments, the command will ask you to
            provide the missing values:

              # command will ask you for the last name
              <info>php %command.full_name%</info> <comment>email password first_name</comment>
              
              # command will ask you for the first and last names
              <info>php %command.full_name%</info> <comment>email password</comment>

              # command will ask you for password, first and last names
              <info>php %command.full_name%</info> <comment>email</comment>

              # command will ask you for all arguments
              <info>php %command.full_name%</info>

            If any of required arguments is missing and <comment>--admin</comment> option is not provided,
            the command will also ask you if you wish to create a regular or an administrator user.

            HELP;
    }
}
