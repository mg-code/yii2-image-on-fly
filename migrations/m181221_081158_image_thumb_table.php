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
              `image_id` int(10) unsigned NOT NULL,
              `type` smallint(2) unsigned NOT NULL,
              `signature` char(32) NOT NULL,
              `width` smallint(4) unsigned DEFAULT NULL,
              `height` smallint(4) unsigned DEFAULT NULL,
              `jpeg_quality` tinyint(2) unsigned DEFAULT NULL,
              `ratio` enum('min','max') DEFAULT NULL,
              `blur` tinyint(2) unsigned DEFAULT NULL,
              `no_zoom_in` tinyint(1) unsigned DEFAULT NULL,
              `crop` tinyint(1) unsigned DEFAULT NULL,
              `background` char(7) DEFAULT NULL,
              `normalize` tinyint(1) unsigned DEFAULT NULL,
              `auto_gamma` tinyint(1) unsigned DEFAULT NULL,
              PRIMARY KEY (`type`,`image_id`),
              UNIQUE KEY `U_signature` (`signature`),
              UNIQUE KEY `U_all_columns` (`image_id`,`width`,`height`,`jpeg_quality`,`ratio`,`blur`,`no_zoom_in`,`crop`,`background`,`normalize`,`auto_gamma`),
              CONSTRAINT `fk_image_thumb_image_id` FOREIGN KEY (`image_id`) REFERENCES `image` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
