<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%document}}`.
 */
class m190607_195557_create_document_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%document}}', [
            'id' => $this->primaryKey(),
            'user_id' => $this->integer()->notNull(),
            'file' => $this->string(100)->notNull(),
            'surname' => $this->string(100)->defaultValue(''),
            'name' => $this->string(100)->defaultValue(''),
            'patronymic' => $this->string(100)->defaultValue(''),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'description' => $this->string(200)->defaultValue(''),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ], $tableOptions);

        $this->addForeignKey(
            'fk-post-user_id',
            'document',
            'user_id',
            'user',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropForeignKey(
            'fk-post-user_id',
            'document'
        );

        $this->dropTable('{{%document}}');
    }
}
