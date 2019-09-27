# Yii LinkMany

Load, validate and save automatically `hasMany` relations.


[![Build Status](https://travis-ci.org/solutosoft/yii-linkmany.svg?branch=master)](https://travis-ci.org/solutosoft/yii-linkmany)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/solutosoft/yii-linkmany/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/solutosoft/yii-linkmany/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/solutosoft/yii-linkmany/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/solutosoft/yii-linkmany/?branch=master)
[![Total Downloads](https://poser.pugx.org/solutosoft/yii-linkmany/downloads.png)](https://packagist.org/packages/solutosoft/yii-linkmany)
[![Latest Stable Version](https://poser.pugx.org/solutosoft/yii-linkmany/v/stable.png)](https://packagist.org/packages/solutosoft/yii-linkmany)


Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist solutosoft/yii-linkmany
```

or add

```json
"solutosoft/yii-linkmany": "*"
```

to the require section of your composer.json.


Usage
-----

This extension provides support for ActiveRecord `hasMany` relation saving.
This support is granted via [[\solutosoft\linkmany\LinkManyBehavior]] ActiveRecord behavior. You'll need to attach
it to your ActiveRecord class and point the target "has-many" relation for it:

```php
class Post extends ActiveRecord
{
    public function behaviors()
    {
        return [
            'linkManyBehavior' => [
                'class' => LinkManyBehavior::class,
                'relations' => [
                    'tags',
                    'messages' => [
                        'formName'  => 'Post[messages]',
                        'validate' => false,
                        'deleteOnUnlink' => false
                    ]
                ]
            ],
        ];
    }

    public function getMessages()
    {
        return $this->hasMany(Message::class, ['post_id' => 'id']);
    }

    public function getTags()
    {
        return $this->hasMany(Tag::class, ['id' => 'tag_id'])
            ->viaTable('post_tag', ['post_id' => 'id']);
    }
}
```

Being attached [[\solutosoft\linkmany\LinkManyBehavior]] you can load data using the method [[\solutosoft\linkmany\LinkManyBehavior::fill]]

```php
use yii\web\Controller;

class PostController extends Controller
{
    public function actionCreate()
    {
        $model = new Post();

        if ($model->fill(Yii::$app->request->post())) {
            $model->save(); // save the model and relations
            return $this->redirect(['view']);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }
}
```

## Credits

This Package is inspired by:

- [la-haute-societe/yii2-save-relations-behavior](https://github.com/la-haute-societe/yii2-save-relations-behavior)
- [yii2tech/ar-linkmany](https://github.com/yii2tech/ar-linkmany).

I wanted to have a combination of both. Thanks to both authors.