<?php
/** @var string $modelClass */
/** @var string $modelClassName */
/** @var \wsl\gii\generators\formmodel\Generator $generator */
/** @var yii\db\TableSchema $tableSchema */
/** @var string[] $pks */
/** @var string $labelsCode */
/** @var string $formModelClass */
/** @var string $createFieldsCode */
/** @var string $updateFieldsCode */
/** @var string $updateStatusFieldsCode */
/** @var string $saveFieldsCode */
/** @var string $deleteFieldsCode */
/** @var boolean $isFormDirExistModel */
/** @var array $pluralizePks */

use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\ArrayHelper;

$modelVarName = Inflector::variablize($modelClassName) . 'Model';
$className = StringHelper::basename($formModelClass);
$baseClassName = StringHelper::basename($generator->baseClass);
$referenceModelClass = $isFormDirExistModel ? StringHelper::basename($generator->modelClass) : $generator->modelClass;

echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->formModelClass, '\\')) ?>;

<?php if ($className != $baseClassName): ?>
use <?= $generator->baseClass ?>;
<?php endif ?>
<?php if (!$isFormDirExistModel): ?>
use <?= $generator->modelClass ?>;
<?php endif ?>

/**
 * This is the Model class for [[<?= $referenceModelClass ?>]].
 *
 * @see <?= $referenceModelClass . "\n" ?>
 */
class <?= $className ?> extends <?= ($className == $baseClassName ? $generator->baseClass : $baseClassName). "\n" ?>
{
    /**
     * 场景 创建
     */
    const SCENARIO_CREATE = 'create';
    /**
     * 场景 更新
     */
    const SCENARIO_UPDATE = 'update';
    /**
     * 场景 更新状态
     */
    const SCENARIO_UPDATE_STATUS = 'updateStatus';
    /**
     * 场景 保存
     */
    const SCENARIO_SAVE = 'save';
    /**
     * 场景 删除
     */
    const SCENARIO_DELETE = 'delete';

<?php foreach ($pks as $pk): ?>
    /**
     * @var integer[] <?= ArrayHelper::getValue($tableSchema->columns, $pk . '.comment') . "集\n" ?>
     */
    public $<?= Inflector::pluralize($pk) ?> = [];
<?php endforeach ?>
<?php foreach ($tableSchema->columns as $name => $column): ?>
    /**
     * @var <?= 'integer' != $column->phpType && 'integer' != $column->type ? 'string' : 'integer' ?> <?= $column->comment . "\n" ?>
     */
    public $<?= $name ?>;
<?php endforeach ?>

    /**
     * @inheritDoc
     */
    public function scenarios()
    {
        return [
            static::SCENARIO_CREATE => <?= $createFieldsCode ?>,
            static::SCENARIO_UPDATE => <?= $updateFieldsCode ?>,
            static::SCENARIO_UPDATE_STATUS => <?= $updateStatusFieldsCode ?>,
            static::SCENARIO_SAVE => <?= $saveFieldsCode ?>,
            static::SCENARIO_DELETE => <?= $deleteFieldsCode ?>,
        ];
    }

    /**
     * @inheritDoc
     */
    public function rules()
    {
        return [<?= "\n            " . implode(",\n            ", $rules) . "\n        " ?>];
    }

    /**
     * @inheritDoc
     */
    public function attributeLabels()
    {
        return <?= $labelsCode ?>;
    }

    /**
     * 创建
     *
     * @return bool
     */
    public function create()
    {
        if ($this->validate()) {
            $<?= $modelVarName ?> = new <?= $modelClassName ?>();
<?php foreach ($tableSchema->columns as $name => $column):
    if (in_array($name, $pks)) {
        continue;
    }
    ?>
            $<?= $modelVarName ?>-><?= $name ?> = $this-><?= $name ?>;
<?php endforeach; ?>
            if ($<?= $modelVarName ?>->save()) {
                return true;
            } else {
                if ($<?= $modelVarName ?>->hasErrors()) {
                    $this->addErrors($<?= $modelVarName ?>->errors);
                }
            }
        }

        return false;
    }

    /**
     * 更新
     *
     * @return bool
     */
    public function update()
    {
        if ($this->validate()) {
            $<?= $modelVarName ?> = <?= $modelClassName ?>::find()
<?php foreach ($pks as $primaryKey): ?>
                ->findBy<?= Inflector::classify($primaryKey)?>($this-><?= $primaryKey ?>)
<?php endforeach; ?>
                ->one();
            if ($<?= $modelVarName ?>) {
<?php foreach ($tableSchema->columns as $name => $column):
    if (in_array($name, $pks)) {
        continue;
    }
    ?>
                $<?= $modelVarName ?>-><?= $name ?> = $this-><?= $name ?>;
<?php endforeach; ?>
                if ($<?= $modelVarName ?>->save()) {
                    return true;
                } else {
                    if ($<?= $modelVarName ?>->hasErrors()) {
                        $this->addErrors($<?= $modelVarName ?>->errors);
                    }
                }
            }
        }

        return false;
    }

    /**
     * 更新状态
     *
     * @return bool
     */
    public function updateStatus()
    {
        if ($this->validate()) {
<?php foreach ($tableSchema->primaryKey as $primaryKey): ?>
            foreach ($this-><?= Inflector::pluralize($primaryKey) ?> as $<?= $primaryKey ?>) {
                $<?= $modelVarName ?> = <?= $modelClassName ?>::find()
                    ->findBy<?= Inflector::classify($primaryKey)?>($<?= $primaryKey ?>)
                    ->one();
                if ($<?= $modelVarName ?>) {
<?php if (isset($tableSchema->columns['status'])): ?>
                    $<?= $modelVarName ?>->status = $this->status;
<?php endif ?>
                    if (!$<?= $modelVarName ?>->save()) {
                        if ($<?= $modelVarName ?>->hasErrors()) {
                            $this->addErrors($<?= $modelVarName ?>->errors);
                        }
                    }
                }
            }
<?php endforeach ?>
            return true;
        }

        return false;
    }

    /**
     * 保存
     *
     * @return bool
     */
    public function save()
    {
        if ($this->validate()) {
            if (<?= $generator->generateActionParams('$this->', ' && ') ?>) {
                $<?= $modelVarName ?> = <?= $modelClassName ?>::find()
<?php foreach ($pks as $primaryKey): ?>
                    ->findBy<?= Inflector::classify($primaryKey)?>($this-><?= $primaryKey ?>)
<?php endforeach; ?>
                    ->one();
            } else {
                $<?= $modelVarName ?> = new <?= $modelClassName ?>();
            }
            if ($<?= $modelVarName ?>) {
<?php foreach ($tableSchema->columns as $name => $column):
    if (in_array($name, $pks)) {
        continue;
    }
    ?>
                $<?= $modelVarName ?>-><?= $name ?> = $this-><?= $name ?>;
<?php endforeach; ?>
                if ($<?= $modelVarName ?>->save()) {
                    return true;
                } else {
                    if ($<?= $modelVarName ?>->hasErrors()) {
                        $this->addErrors($<?= $modelVarName ?>->errors);
                    }
                }
            }
        }

        return false;
    }

    /**
     * 标记删除状态
     *
     * @return bool
     */
    public function delete()
    {
        if ($this->validate()) {
<?php foreach ($tableSchema->primaryKey as $primaryKey): ?>
            foreach ($this-><?= Inflector::pluralize($primaryKey) ?> as $<?= $primaryKey ?>) {
                $<?= $modelVarName ?> = <?= $modelClassName ?>::find()
                    ->findBy<?= Inflector::classify($primaryKey)?>($<?= $primaryKey ?>)
                    ->one();
                if ($<?= $modelVarName ?>) {
<?php if (isset($tableSchema->columns['status'])): ?>
                    $<?= $modelVarName ?>->status = $<?= $modelVarName ?>::STATUS_DELETE;
<?php endif ?>
                    if (!$<?= $modelVarName ?>->save()) {
                        if ($<?= $modelVarName ?>->hasErrors()) {
                            $this->addErrors($<?= $modelVarName ?>->errors);
                        }
                    }
                }
            }
<?php endforeach ?>
            return true;
        }

        return false;
    }

    /**
     * 删除
     *
     * @return bool
     */
    /*public function delete()
    {
        if ($this->validate()) {
<?php foreach ($tableSchema->primaryKey as $primaryKey): ?>
            foreach ($this-><?= Inflector::pluralize($primaryKey) ?> as $<?= $primaryKey ?>) {
                $<?= $modelVarName ?> = <?= $modelClassName ?>::find()
                    ->findBy<?= Inflector::classify($primaryKey)?>($<?= $primaryKey ?>)
                    ->one();
                if ($<?= $modelVarName ?>) {
                    if (!$<?= $modelVarName ?>->delete()) {
                        if ($<?= $modelVarName ?>->hasErrors()) {
                            $this->addErrors($<?= $modelVarName ?>->errors);
                        }
                    }
                }
            }
<?php endforeach ?>
            return true;
        }

        return false;
    }*/
}