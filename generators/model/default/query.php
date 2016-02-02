<?php
/**
 * This is the template for generating the ActiveQuery class.
 */

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\model\Generator */
/* @var $className string class name */
/* @var $modelClassName string related model class name */
/* @var $tableSchema \yii\db\TableSchema */

use yii\helpers\Inflector;

$modelFullClassName = $modelClassName;
if ($generator->ns !== $generator->queryNs) {
    $modelFullClassName = '\\' . $generator->ns . '\\' . $modelFullClassName;
}

echo "<?php\n";
?>

namespace <?= $generator->queryNs ?>;

/**
 * This is the ActiveQuery class for [[<?= $modelFullClassName ?>]].
 *
 * @see <?= $modelFullClassName . "\n" ?>
 */
class <?= $className ?> extends <?= '\\' . ltrim($generator->queryBaseClass, '\\') . "\n" ?>
{
<?php if (isset($tableSchema->columns['status'])) :?>
    /**
     * 禁用
     */
    public function inactive()
    {
        $this->andWhere([
            <?= $modelFullClassName ?>::tableName() . '.[[status]]' => <?= $modelFullClassName ?>::STATUS_INACTIVE,
        ]);
        return $this;
    }

    /**
     * 启用
     */
    public function active()
    {
        $this->andWhere([
            <?= $modelFullClassName ?>::tableName() . '.[[status]]' => <?= $modelFullClassName ?>::STATUS_ACTIVE,
        ]);
        return $this;
    }

    /**
     * 删除
     */
    public function delete()
    {
        $this->andWhere([
            <?= $modelFullClassName ?>::tableName() . '.[[status]]' => <?= $modelFullClassName ?>::STATUS_DELETE,
        ]);
        return $this;
    }

    /**
     * 正常
     */
    public function normal()
    {
        $this->andWhere([
            'IN', <?= $modelFullClassName ?>::tableName() . '.[[status]]', [<?= $modelFullClassName ?>::STATUS_INACTIVE, <?= $modelFullClassName ?>::STATUS_ACTIVE],
        ]);
        return $this;
    }
<?php endif ?>
<?php foreach ($tableSchema->columns as $column): ?>

    /**
     * Find by `<?= $column->name?>`
     *
     * @param <?= "{$column->phpType} \$"?><?=Inflector::variablize($column->name)?> [[<?= $column->name?>]]
     * @return $this
     */
    public function findBy<?= 'condition' == $column->name ? Inflector::camelize('My ' . $column->name) : Inflector::camelize($column->name)?>($<?=Inflector::variablize($column->name)?>)
    {
        $this->andWhere([
            <?= $modelFullClassName ?>::tableName() . '.[[<?= $column->name ?>]]' => $<?= Inflector::variablize($column->name) ?>,
        ]);
        return $this;
    }
<?php if('string' == $column->phpType):?>

    /**
     * Find by like `<?= $column->name?>`
     *
     * @param <?= "{$column->phpType} \$"?><?=Inflector::variablize($column->name)?> [[<?= $column->name?>]]
     * @return $this
     */
    public function findByLike<?= 'condition' == $column->name ? Inflector::camelize('My ' . $column->name) : Inflector::camelize($column->name)?>($<?=Inflector::variablize($column->name)?>)
    {
        $this->andWhere([
            'like', <?= $modelFullClassName ?>::tableName() . '.[[<?= $column->name ?>]]', $<?= Inflector::variablize($column->name) ?>,
        ]);
        return $this;
    }

    /**
     * Find by like left `<?= $column->name?>`
     *
     * @param <?= "{$column->phpType} \$"?><?=Inflector::variablize($column->name)?> [[<?= $column->name?>]]
     * @return $this
     */
    public function findByLeftLike<?= 'condition' == $column->name ? Inflector::camelize('My ' . $column->name) : Inflector::camelize($column->name)?>($<?=Inflector::variablize($column->name)?>)
    {
        $this->andWhere([
            'like', <?= $modelFullClassName ?>::tableName() . '.[[<?= $column->name ?>]]', '%' . $<?= Inflector::variablize($column->name) ?>, false
        ]);
        return $this;
    }

    /**
     * Find by like right `<?= $column->name?>`
     *
     * @param <?= "{$column->phpType} \$"?><?=Inflector::variablize($column->name)?> [[<?= $column->name?>]]
     * @return $this
     */
    public function findByRightLike<?= 'condition' == $column->name ? Inflector::camelize('My ' . $column->name) : Inflector::camelize($column->name)?>($<?=Inflector::variablize($column->name)?>)
    {
        $this->andWhere([
            'like', <?= $modelFullClassName ?>::tableName() . '.[[<?= $column->name ?>]]', $<?= Inflector::variablize($column->name) ?> . '%', false
        ]);
        return $this;
    }
<?php endif;?>
<?php endforeach; ?>

    /**
     * @inheritdoc
     * @return <?= $modelFullClassName ?>[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return <?= $modelFullClassName ?>|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }

}