<?php

use yii\db\Migration;

/**
 * Class m181221_081158_image_thumb_table
 */
class m181221_081158_image_thumb_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->execute("
            CREATE TABLE `image_thumb` (
              `signature` CHAR(32) NOT NULL,
              `image_id` INT UNSIGNED NOT NULL,
              `width` SMALLINT(4) UNSIGNED NULL,
              `height` SMALLINT(4) UNSIGNED NULL,
              `jpeg_quality` TINYINT(2) UNSIGNED NULL,
              `ratio` ENUM('min', 'max') NULL,
              `blur` TINYINT(2) UNSIGNED NULL,
              `no_zoom_in` TINYINT(1) UNSIGNED NULL,
              `crop` TINYINT(1) UNSIGNED NULL,
              `background` CHAR(7) NULL,
              `normalize` TINYINT(1) UNSIGNED NULL,
              `auto_gamma` TINYINT(1) UNSIGNED NULL,
              PRIMARY KEY (`signature`),
              UNIQUE INDEX `U_all_columns` (`image_id`, `width`, `height`, `jpeg_quality`, `ratio`, `blur`, `no_zoom_in`, `crop`, `background`, `normalize`, `auto_gamma`),
              CONSTRAINT `fk_image_thumb_image_id`
                FOREIGN KEY (`image_id`)
                REFERENCES `image` (`id`)
                ON DELETE CASCADE
                ON UPDATE CASCADE);
        ");
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('image_thumb');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181221_081158_image_thumb_table cannot be reverted.\n";

        return false;
    }
    */
}
