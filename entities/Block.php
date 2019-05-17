<?php

namespace abdualiym\block\entities;

use abdualiym\block\helpers\Type;
use abdualiym\block\validators\SlugValidator;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * @property integer $id
 * @property integer $parent_id
 * @property string $label
 * @property string $slug
 * @property integer $data_type
 * @property string $data_0
 * @property string $data_1
 * @property string $data_2
 * @property string $data_3
 * @property integer $created_at
 * @property integer $updated_at
 */
class Block extends ActiveRecord
{
    // TODO
    //  parenting
    //  optimise for show-view: parents / types queue
    //  url, redirects and breadcrumbs
    //  test all types
    //  translate labels
    //  drop action
    //  site widget example for support: cache / dropped items skipping error and show slug+info

    public static function tableName(): string
    {
        return '{{%yii2_blocks}}';
    }

    public function rules(): array
    {
        return [
            [['label', 'slug', 'data_type'], 'required'],

            [['label', 'slug'], 'string', 'max' => 255],
            [['slug'], SlugValidator::class],

            [['data_type'], 'in', 'range' => array_keys(Type::list())],

            [['parent_id'], 'integer'],

            [
                ['data_0', 'data_1', 'data_2', 'data_3'], 'image',
                'when' => function (self $model) {
                    return in_array($model->data_type, [Type::IMAGES, Type::IMAGE_COMMON]);
                }, 'enableClientValidation' => false
            ],
            [
                ['data_0', 'data_1', 'data_2', 'data_3'], 'file',
                'when' => function (self $model) {
                    return in_array($model->data_type, [Type::FILES, Type::FILE_COMMON]);
                }, 'enableClientValidation' => false
            ],
            [
                ['data_0', 'data_1', 'data_2', 'data_3'], 'url', 'defaultScheme' => 'http',
                'when' => function (self $model) {
                    return in_array($model->data_type, [Type::LINKS, Type::LINK_COMMON]);
                }, 'enableClientValidation' => false
            ],
            [
                ['data_0', 'data_1', 'data_2', 'data_3'], 'string',
                'when' => function (self $model) {
                    return in_array($model->data_type, [Type::STRINGS, Type::STRING_COMMON, Type::TEXTS, Type::TEXT_COMMON]);
                }, 'enableClientValidation' => false
            ],
        ];
    }

    ####################################

    public function isCommon(): bool
    {
        return in_array($this->data_type, [Type::STRING_COMMON, Type::TEXT_COMMON, Type::IMAGE_COMMON, Type::LINK_COMMON, Type::FILE_COMMON]);
    }


    public function isFile(): bool
    {
        return in_array($this->data_type, [Type::IMAGES, Type::IMAGE_COMMON, Type::FILES, Type::FILE_COMMON]);
    }


    public function showData($key = 0)
    {
        $data = 'data_' . $key;

        if (in_array($this->data_type, [Type::IMAGES, Type::IMAGE_COMMON])) {
            return Html::img($this->getUploadedFileUrl($data));
        }

        if (in_array($this->data_type, [Type::FILES, Type::FILE_COMMON])) {
            return Html::a('<i class="fa fa-file"></i> file', $this->getUploadedFileUrl($data), ['target' => '_blank']);
        }

        if (in_array($this->data_type, [Type::LINKS, Type::LINK_COMMON])) {
            return Html::a('<i class="fa fa-link"></i> link', $data, ['target' => '_blank']);
        }

        return $this->$data;
    }


    ####################################

    public function parentList(): array
    {
        return ArrayHelper::map(Block::find()->where(['parent_id' => null])->asArray()->all(), 'id', 'label');
    }

    public function getParent(): ActiveQuery
    {
        return $this->hasOne(self::class, ['id' => 'parent_id']);
    }

    ####################################

    public function afterFind()
    {
        parent::afterFind(); // TODO: Change the autogenerated stub

        self::attachBehaviors(
            array_merge(
                [TimestampBehavior::class],
                Type::config($this->data_type)
            )
        );

    }

}