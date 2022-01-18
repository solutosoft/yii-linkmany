<?php

namespace solutosoft\linkmany\tests;

use solutosoft\linkmany\LinkManyBehavior;
use solutosoft\linkmany\tests\models\Post;
use solutosoft\linkmany\tests\models\PostComment;
use solutosoft\linkmany\tests\models\Tag;

class LinkManyBehaviorTest extends TestCase
{
    public function testInitSuccess()
    {
        $behavior = new LinkManyBehavior([
            'relations' => [
                'relation_1',
                'relation_2' => [
                    'formName' => 'test'
                ]
            ]
        ]);

        $this->assertCount(2, $behavior->relations);
    }

    public function testInitException()
    {
        $this->expectException('\yii\base\InvalidConfigException');
        new LinkManyBehavior([
            'relations' => [
                ['invalid' => true]
            ]
        ]);
    }

    public function testInvalidRelationValue()
    {
        $this->expectException('\yii\base\InvalidArgumentException');
        $post = new Post();
        $post->comments = null;
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

    public function testUpdateHasMany()
    {
        $post = Post::findOne(1);
        $post->fill([
            'title' => 'modified title',
            'comments' => [
                [
                    'id' => 1,
                    'subject' => 'modified subject',
                    'content' => 'modified content'
                ]
            ]
        ],'');

        $post->save();
        $post = Post::findOne(1);
        $this->assertCount(1, $post->comments);

        $comment = $post->comments[0];
        $this->assertEquals('modified subject', $comment->subject);
        $this->assertEquals('modified content', $comment->content);

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
                [],
                [
                    'id' => 1,
                    'subject' => 1234
                ]
            ]
        ],'');

        $post->validate();

        $errors = $post->getErrors();
        $this->assertArrayHasKey('comments', $errors);
        $this->assertCount(2, $errors['comments']);
    }

    public function testValidateErrorIndex()
    {
        $post = Post::findOne(1);
        $post->fill([
            'comments' => [
                [
                    'subject' => 'Valid subject',
                    'content' => 'Valid content',
                ],[
                    'subject' => 'Valid subject',
                    'content' => 6789
                ],[
                    'subject' => 1234
                ],
            ]
        ],'');

        $post->validate();

        $this->assertEquals([
            'comments[1]' => [
                'Content must be a string.'
            ],
            'comments[2]' => [
                'Subject must be a string.',
                'Content cannot be blank.'
            ]
        ], $post->getErrors());
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
        $post->refresh();
        $this->assertCount(1, $post->tags);
    }

    public function testScenario()
    {
        $post = Post::findOne(1);
        $post->attachBehavior('linkMany', new LinkManyBehavior([
           'relations' => [
                'tags' => [
                    'scenario' => Tag::SCENARIO_LINK
                ],
                'comments' => [
                    'scenario' => PostComment::SCENARIO_LINK
                ]
            ]
        ]));

        $post->fill([
            'title' => 'modified title',
            'tags' => [1, 3],
            'comments' => [
                [
                    'id' => 1,
                    'subject' => 'modified subject',
                    'content' => 'modified content'
                ], [
                    'subject' => 'new subject',
                    'content' => 'new content'
                ]
            ]
        ], '');

        foreach ($post->tags as $tag) {
            $this->assertEquals(Tag::SCENARIO_LINK, $tag->scenario);
        }

        foreach ($post->comments as $comment) {
            $this->assertEquals(PostComment::SCENARIO_LINK, $comment->scenario);
        }

    }
}
