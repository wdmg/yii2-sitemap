<?php

use yii\db\Migration;

/**
 * Class m200105_205517_sitemap
 */
class m200105_205517_sitemap extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

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

    }
}
