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
    /*public function inactive()
    {
        $this->andWhere('[[status]]=0');
        return $this;
    }

    public function active()
    {
        $this->andWhere('[[status]]=1');
        return $this;
    }

    public function delete()
    {
        $this->andWhere('[[status]]=2');
        return $this;
    }

    public function normal()
    {
        $this->andWhere('[[status]]=0 or [[status]]=1');
        return $this;
    }*/
<?php foreach ($tableSchema->columns as $column): ?>

    /**
     * Find by `<?= $column->name?>`
     *
     * @param <?= "{$column->phpType} \$"?><?=Inflector::variablize($column->name)?> [[<?= $column->name?>]]
     * @return $this
     */
    public function findBy<?= 'condition' == $column->name ? Inflector::camelize('My ' . $column->name) : Inflector::camelize($column->name)?>($<?=Inflector::variablize($column->name)?>)
    {
        $this->andWhere(['[[<?=$column->name?>]]' => $<?=Inflector::variablize($column->name)?>]);
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
        $this->andWhere('[[<?=$column->name?>]] like :<?=$column->name?>', [
            ':<?=$column->name?>' => '%' . $<?=Inflector::variablize($column->name)?> . '%',
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
        $this->andWhere('[[<?=$column->name?>]] like :<?=$column->name?>', [
            ':<?=$column->name?>' => '%' . $<?=Inflector::variablize($column->name)?>,
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
        $this->andWhere('[[<?=$column->name?>]] like :<?=$column->name?>', [
            ':<?=$column->name?>' => $<?=Inflector::variablize($column->name)?> . '%',
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