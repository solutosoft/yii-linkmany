<?php

namespace solutosoft\linkmany\tests;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * Base class for the test cases.
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
        $this->setupDatabase();
    }

    protected function tearDown(): void
    {
        $this->destroyApplication();
    }

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [])
    {
        new \yii\web\Application(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'sqlite::memory:',
                    /*'dsn' => 'mysql:host=mysql;dbname=audit-record',
                    'username' => 'root',
                    'password' => 'root',
                    'charset' => 'utf8',
                    'enableSchemaCache' => false*/

                ],
                'user' => [
                    'identityClass' => 'Soluto\Tests\Data\Person',
                    'enableSession' => false
                ],
                'request' => [
                    'cookieValidationKey' => 'audit-cookie-key',
                    'scriptFile' => __DIR__ .'/index.php',
                    'scriptUrl' => '/index.php',
                ]
            ]

        ], $config));
    }

    /**
     * Destroys application in Yii::$app by setting it to null.
     */
    protected function destroyApplication()
    {
        Yii::$app = null;
    }

    /**
     * Setup tables for test ActiveRecord
     */
    protected function setupDatabase()
    {
        $db = Yii::$app->getDb();

        $db->createCommand()->createTable('author', [
            'id' => 'pk',
            'name' => 'string',
            'image' => 'string'
        ])->execute();

        $db->createCommand()->createTable('post', [
            'id' => 'pk',
            'title' => 'string',
            'content' => 'text',
            'author_id' => 'integer'
        ])->execute();

        $db->createCommand()->createTable('tag', [
            'id' => 'pk',
            'name' => 'string',
            'color' => 'string'
        ])->execute();

        $db->createCommand()->createTable('post_tag', [
            'post_id' => 'integer',
            'tag_id' => 'integer'
        ])->execute();

        $db->createCommand()->createTable('post_language', [
            'post_id' => 'integer',
            'language' => 'string',
            'url' => 'string',
            'PRIMARY KEY(post_id, language)'
        ])->execute();

        $db->createCommand()->createTable('post_comment', [
            'id' => 'pk',
            'post_id' => 'integer',
            'subject' => 'string',
            'content' => 'string'
        ])->execute();

        $db->createCommand()->batchInsert('tag', ['id', 'name', 'color'], [
            [1, 'tag_1', '#aaaaaa'],
            [2, 'tag_2', '#bbbbbb'],
            [3, 'tag_3', '#cccccc'],
        ])->execute();

        $db->createCommand()->insert('author', [
            'id' => 1,
            'name' => 'Author 1',
            'image' => '/img/avatar1.png'
        ])->execute();

        $db->createCommand()->insert('post', [
            'id' => 1,
            'title' => 'Post 1',
            'content' => 'The first post',
            'author_id' => 1
        ])->execute();

        $db->createCommand()->batchInsert('post_tag', ['post_id', 'tag_id'],[
            [1, 1],
            [1, 2],
        ])->execute();

        $db->createCommand()->batchInsert('post_language', ['post_id', 'language', 'url'], [
            [1, 'en', '/posts/en'],
            [1, 'pt-br', '/posts/pt-br'],
        ])->execute();

        $db->createCommand()->batchInsert('post_comment', ['id', 'post_id', 'subject', 'content'], [
            [1, 1, 'subject 1', 'comment 1']
        ])->execute();

    }
}
