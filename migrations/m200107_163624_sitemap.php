<?php

use yii\db\Migration;

/**
 * Class m200107_163624_sitemap
 */
class m200107_163624_sitemap extends Migration
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

        $this->createTable('{{%sitemap}}', [
            'id' => $this->primaryKey(),

            'url' => $this->string(255)->unique()->notNull(),
            'changefreq' => $this->string(16)->notNull(),
            'priority' => $this->double(2)->notNull()->defaultValue(1),

            'created_at' => $this->dateTime()->defaultExpression('CURRENT_TIMESTAMP'),
            'created_by' => $this->integer(11),
            'updated_at' => $this->datetime()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_by' => $this->integer(11),
        ], $tableOptions);

        // Add author foreign key`s
        if (class_exists('\wdmg\users\models\Users')) {
            $this->createIndex('{{%idx-sitemap-author}}','{{%sitemap}}', ['created_by', 'updated_by'], false);
            $userTable = \wdmg\users\models\Users::tableName();
            if (!(Yii::$app->db->getTableSchema($userTable, true) === null)) {
                $this->addForeignKey(
                    'fk_sitemap_created2users',
                    '{{%sitemap}}',
                    'created_by',
                    $userTable,
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
                $this->addForeignKey(
                    'fk_sitemap_updated2users',
                    '{{%sitemap}}',
                    'updated_by',
                    $userTable,
                    'id',
                    'CASCADE',
                    'CASCADE'
                );
            }
        }

        // Add support models
        if (class_exists('\wdmg\pages\models\Pages')) {
            $userTable = \wdmg\pages\models\Pages::tableName();

            if (is_null($this->getDb()->getSchema()->getTableSchema($userTable)->getColumn('in_sitemap')))
                $this->addColumn($userTable, 'in_sitemap', $this->boolean()->defaultValue(true));

        }
        if (class_exists('\wdmg\news\models\News')) {
            $userTable = \wdmg\news\models\News::tableName();

            if (is_null($this->getDb()->getSchema()->getTableSchema($userTable)->getColumn('in_sitemap')))
                $this->addColumn($userTable, 'in_sitemap', $this->boolean()->defaultValue(true));

        }

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {

        // Remove support models
        if (class_exists('\wdmg\pages\models\Pages')) {
            $userTable = \wdmg\pages\models\Pages::tableName();

            if (!is_null($this->getDb()->getSchema()->getTableSchema($userTable)->getColumn('in_sitemap')))
                $this->dropColumn($userTable, 'in_sitemap');

        }

        if (class_exists('\wdmg\news\models\News')) {
            $userTable = \wdmg\news\models\News::tableName();

            if (!is_null($this->getDb()->getSchema()->getTableSchema($userTable)->getColumn('in_sitemap')))
                $this->dropColumn($userTable, 'in_sitemap');

        }

        // Remove author foreign key`s
        if (class_exists('\wdmg\users\models\Users')) {
            $userTable = \wdmg\users\models\Users::tableName();
            if (!(Yii::$app->db->getTableSchema($userTable, true) === null)) {
                $this->dropIndex('{{%idx-sitemap-author}}', '{{%sitemap}}');
                $this->dropForeignKey(
                    'fk_sitemap_created2users',
                    '{{%sitemap}}'
                );
                $this->dropForeignKey(
                    'fk_sitemap_updated2users',
                    '{{%sitemap}}'
                );
            }
        }

    }
}
