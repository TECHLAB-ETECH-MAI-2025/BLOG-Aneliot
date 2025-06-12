<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250608073629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE article ADD author_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article ADD CONSTRAINT FK_23A0E66F675F31B FOREIGN KEY (author_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_23A0E66F675F31B ON article (author_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like ADD user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like DROP ip_address
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like ALTER article_id SET NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like ADD CONSTRAINT FK_1C21C7B2A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1C21C7B2A76ED395 ON article_like (user_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD user_id INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP author
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_9474526CA76ED395 ON comment (user_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE SCHEMA public
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP CONSTRAINT FK_9474526CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_9474526CA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment ADD author VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE comment DROP user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article DROP CONSTRAINT FK_23A0E66F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_23A0E66F675F31B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article DROP author_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like DROP CONSTRAINT FK_1C21C7B2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1C21C7B2A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like ADD ip_address VARCHAR(45) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like DROP user_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE article_like ALTER article_id DROP NOT NULL
        SQL);
    }
}
