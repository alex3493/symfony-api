<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240828121946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE auth_token (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', user_id CHAR(36) DEFAULT NULL COMMENT \'(DC2Type:guid)\', name VARCHAR(255) NOT NULL, token VARCHAR(255) NOT NULL, created DATETIME NOT NULL, last_used DATETIME DEFAULT NULL, expires DATETIME DEFAULT NULL, INDEX IDX_9315F04EA76ED395 (user_id), INDEX IDX_9315F04E5E237E06 (name), UNIQUE INDEX user_device_unique (name, user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE refresh_tokens (id INT AUTO_INCREMENT NOT NULL, refresh_token VARCHAR(255) DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, valid DATETIME DEFAULT NULL, INDEX IDX_9BACE7E1F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_tokens (email VARCHAR(255) NOT NULL, reset_token VARCHAR(255) DEFAULT NULL, valid_until DATETIME DEFAULT NULL, PRIMARY KEY(email)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id CHAR(36) NOT NULL COMMENT \'(DC2Type:guid)\', email VARCHAR(255) NOT NULL, password VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, roles JSON NOT NULL, created DATETIME NOT NULL, updated DATETIME DEFAULT NULL, deleted DATETIME DEFAULT NULL, UNIQUE INDEX email_unique (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE auth_token ADD CONSTRAINT FK_9315F04EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE auth_token DROP FOREIGN KEY FK_9315F04EA76ED395');
        $this->addSql('DROP TABLE auth_token');
        $this->addSql('DROP TABLE refresh_tokens');
        $this->addSql('DROP TABLE reset_password_tokens');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
