<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240828121947 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create default Admin user';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $pass = '$2y$13$cPs262Yfa67OJMjZaURCWOfdz0S94oi1BiWZvml7cYt54GMTh2siG'; // "password"
        $this->addSql("INSERT INTO `user` (id,email,password,first_name,last_name,roles,created,updated,deleted)
                       VALUES ('26d83333-0224-11eb-961f-0242ac120002','admin@example.com','{$pass}','','','[\"ROLE_ADMIN\"]','2024-07-01 12:00:00', NULL, NULL);");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
