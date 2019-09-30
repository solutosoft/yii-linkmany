<?php

namespace solutosoft\linkmany\tests;

use solutosoft\linkmany\LinkManyBehavior;
use solutosoft\linkmany\tests\models\Post;
use solutosoft\linkmany\tests\models\Tag;

//use solutosoft\linkmany\tests\models\PostLanguage;
//use solutosoft\linkmany\tests\models\Tag;

class LinkManyBehaviorTest extends TestCase
{
    public function testInitException()
    {
        $this->setExpectedException('\yii\base\InvalidConfigException');

        new LinkManyBehavior([
            'relations' => [
                ['invalid' => true]
            ]
        ]);
    }

    public function testInitSuccess()
    {
        new LinkManyBehavior([
            'relations' => [
                'relation_1',
                'relation_2' => [
                    'formName' => 'test'
                ]
            ]
        ]);
    }

    public function testFillNewRecord()
    {
        $post = new Post();
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'tags' => [1, 2],
            'languages' => [
                [
                    'lang' => 'ja',
                    'url' => '/posts/ja'
                ],[
                    'lang' => 'en',
                    'url' => '/posts/en-modified'
                ]
            ]
        ], '');

        $this->assertCount(2, $post->tags);
        $this->assertCount(2, $post->languages);

        $tag = $post->tags[0];

        $this->assertFalse($tag->getIsNewRecord());
        $lang = $post->languages[0];

        $this->assertTrue($lang->getIsNewRecord());

    }

    public function testSaveNewRecord()
    {
        $post = new Post();
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'tags' => [1, 2],
            'languages' => [
                [
                    'lang' => 'ja',
                    'url' => '/posts/ja'
                ],[
                    'lang' => 'en',
                    'url' => '/posts/en-modified'
                ]
            ]
        ],'');


        $post->save();
        $this->assertCount(2, $post->tags);
        $this->assertCount(2, $post->languages);

        foreach ($post->languages as $lang) {
            $this->assertFalse($lang->getIsNewRecord());
        }
    }

    public function testSaveHasMany()
    {
        $post = Post::findOne(1);
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'comments' => [
                [
                    'content' => 'comment 2',
                ],[
                    'content' => 'comment 3',
                ]
            ]
        ],'');


        $post->save();
        $this->assertCount(2, $post->languages);

        $post->refresh();
        $this->assertCount(2, $post->languages);

    }

    public function testSaveHasManyCompositeKey()
    {
        $post = Post::findOne(1);
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'languages' => [
                [
                    'language' => 'ja',
                    'url' => '/posts/ja'
                ],[
                    'language' => 'en',
                    'url' => '/posts/en-modified'
                ]
            ]
        ],'');


        $post->save();
        $this->assertCount(2, $post->languages);

        $post->refresh();
        $this->assertCount(2, $post->languages);

    }


    public function testSaveHasManyViaTable()
    {
        $post = Post::findOne(1);
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'tags' => [1, 3]
        ],'');


        $post->save();
        $this->assertCount(2, $post->tags);

        $post->refresh();
        $this->assertCount(2, $post->tags);

    }

    public function testUnlink()
    {
        $post = Post::findOne(1);
        $post->fill([
            'title' => 'modified title',
            'content' => 'modified content',
            'author_id' => 2,
            'tags' => [3]
        ],'');

        $post->save();
        $this->assertCount(1, $post->tags);
    }

    public function testValidate()
    {
        $post = Post::findOne(1);
        $post->fill([
            'comments' => [
                [],[]
            ]
        ],'');

        $post->validate();

        $errors = $post->getErrors();
        $this->assertArrayHasKey('comments', $errors);
        $this->assertCount(2, $errors['comments']);
    }


    public function testSetRelation()
    {
        $post = Post::findOne(1);
        $post->comments = [
            ['subject' => 'new subject', 'content' => 'new content']
        ];

        $post->save();
        $this->assertCount(1, $post->comments);

        $post->tags = [1];

        $post->save();
        $this->assertCount(1, $post->tags);
    }
}