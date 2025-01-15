<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240301000003 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            player1_id INT NOT NULL,
            player2_id INT NULL,
            player1_card_id INT NOT NULL,
            player2_card_id INT NULL,
            status VARCHAR(20) NOT NULL,
            winner_id INT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            finished_at DATETIME NULL,
            FOREIGN KEY (player1_id) REFERENCES users(id),
            FOREIGN KEY (player2_id) REFERENCES users(id),
            FOREIGN KEY (player1_card_id) REFERENCES cards(id),
            FOREIGN KEY (player2_card_id) REFERENCES cards(id),
            FOREIGN KEY (winner_id) REFERENCES users(id)
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE games');
    }
} 