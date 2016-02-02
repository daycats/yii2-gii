<?php
/**
 * This is the template for generating a CRUD controller class file.
 */

use yii\db\ActiveRecordInterface;
use yii\helpers\StringHelper;
use yii\helpers\Inflector;


/* @var $this yii\web\View */
/* @var $generator \wsl\gii\generators\formmodel\Generator */

$controllerClass = StringHelper::basename($generator->controllerClass);
$modelClass = StringHelper::basename($generator->modelClass);
$formModelClass = StringHelper::basename($generator->formModelClass);
$searchModelClass = StringHelper::basename($generator->searchModelClass);
if ($modelClass === $searchModelClass) {
    $searchModelAlias = $searchModelClass . 'Search';
}

/* @var $class ActiveRecordInterface */
$class = $generator->modelClass;
$pks = $class::primaryKey();
$actionParams = $generator->generateActionParams();
$actionParamComments = $generator->generateActionParamComments();

$uses = [
    'common\helpers\ExtHelper',
    'Yii',
    'yii\helpers\ArrayHelper',
];
$uses[] = 'yii\web\MethodNotAllowedHttpException';
$uses[] = 'yii\web\NotFoundHttpException';
$uses[] = 'yii\filters\VerbFilter';
$baseController = $generator->getBaseControllerInstance();
if (!method_exists($baseController, 'success')) {
    $uses[] = 'yii\web\Response';
}
$uses[] = ltrim($generator->baseControllerClass, '\\');
$uses[] = ltrim($generator->modelClass, '\\');
if (!empty($generator->searchModelClass)) {
    $uses[] = ltrim($generator->searchModelClass, '\\') . (isset($searchModelAlias) ? " as $searchModelAlias" : "");
} else {
    $uses[] = 'yii\data\ActiveDataProvider';
}
$uses[] = ltrim($generator->formModelClass, '\\');

echo "<?php\n";
?>

namespace <?= StringHelper::dirname(ltrim($generator->controllerClass, '\\')) ?>;

<?php foreach ($uses as $use): ?>
use <?= $use ?>;
<?php endforeach ?>

/**
 * <?= $controllerClass ?> implements the CRUD actions for <?= $modelClass ?> model.
 */
class <?= $controllerClass ?> extends <?= StringHelper::basename($generator->baseControllerClass) . "\n" ?>
{
//    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'list' => ['get'],
                    'create' => ['post'],
                    'update' => ['post'],
                    'save' => ['post'],
                    'update-status' => ['post'],
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all <?= $modelClass ?> models.
     *
     * @return string
     */
    public function actionList()
    {
        $data = [
            'list' => [],
            'total' => 0,
        ];
<?php if (!empty($generator->searchModelClass)): ?>
        $searchModel = new <?= isset($searchModelAlias) ? $searchModelAlias : $searchModelClass ?>();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, '');
<?php else: ?>
        $dataProvider = new ActiveDataProvider([
            'query' => <?= $modelClass ?>::find(),
        ]);
<?php endif; ?>
        $dataProvider->pagination->setPageSize(intval(Yii::$app->request->get('limit')));
        $dataProvider->query->orderBy(ExtHelper::getSort(ArrayHelper::getValue(<?= $modelClass ?>::getTableSchema(), 'columns')));
        /** @var <?= $modelClass ?>[] $models */
        $models = $dataProvider->getModels();
        $data['total'] = $dataProvider->totalCount;
        foreach ($models as $itemModel) {
            $data['list'][] = $itemModel->toArray();
        }

        <?= $generator->generateSuccessData(2, '$data') ."\n" ?>
    }

    /**
     * Displays a single <?= $modelClass ?> model.
     *
     * <?= implode("\n     * ", $actionParamComments) . "\n" ?>
     * @return string
     * @throws NotFoundHttpException
     */
    public function actionDetail(<?= $actionParams ?>)
    {
        $model = <?= StringHelper::basename($generator->modelClass) ?>::find()
<?php foreach ($pks as $pk): ?>
            ->findBy<?= Inflector::classify($pk) ?>($<?= $pk ?>)
<?php endforeach ?>
            ->one();

        if ($model) {
            $data = $model->toArray();
            <?= $generator->generateSuccessData(3, '$data') ."\n" ?>
        }

        throw new NotFoundHttpException('数据不存在');
    }

    /**
     * Creates a new <?= $modelClass ?> model.
     *
     * @return string
     * @throws MethodNotAllowedHttpException
     */
    public function actionCreate()
    {
        $formModel = new <?= $formModelClass ?>();

        $formModel->setScenario($formModel::SCENARIO_CREATE);
        if ($formModel->load(Yii::$app->request->post(), '')) {
            if ($formModel->create()) {
                <?= $generator->generateSuccess(4, '\'创建成功\'') ."\n" ?>
            } elseif ($formModel->hasErrors()) {
                foreach ($formModel->errors as $field) {
                    foreach ($field as $message) {
                        <?= $generator->generateError(6, '$message') ."\n" ?>
                    }
                }
            }
        }

        throw new MethodNotAllowedHttpException();
    }

    /**
     * Updates an existing <?= $modelClass ?> model.
     *
     * @return string
     * @throws MethodNotAllowedHttpException
     */
    public function actionUpdate()
    {
        $formModel = new <?= $formModelClass ?>();

        $formModel->setScenario($formModel::SCENARIO_UPDATE);
        if ($formModel->load(Yii::$app->request->post(), '')) {
            if ($formModel->update()) {
                <?= $generator->generateSuccess(4, '\'更新成功\'') ."\n" ?>
            } elseif ($formModel->hasErrors()) {
                foreach ($formModel->errors as $field) {
                    foreach ($field as $message) {
                        <?= $generator->generateError(6, '$message') ."\n" ?>
                    }
                }
            }
        }

        throw new MethodNotAllowedHttpException();
    }

    /**
     * Updates status an existing Page model.
     *
     * @return string
     * @throws MethodNotAllowedHttpException
     */
    public function actionUpdateStatus()
    {
        $formModel = new <?= $formModelClass ?>();

        $formModel->setScenario($formModel::SCENARIO_UPDATE_STATUS);
        if ($formModel->load(Yii::$app->request->post(), '')) {
            if ($formModel->updateStatus()) {
                <?= $generator->generateSuccess(4, '\'更新成功\'') ."\n" ?>
            } elseif ($formModel->hasErrors()) {
                foreach ($formModel->errors as $field) {
                    foreach ($field as $message) {
                        <?= $generator->generateError(6, '$message') ."\n" ?>
                    }
                }
            }
        }

        throw new MethodNotAllowedHttpException();
    }

    /**
     * Save new or an existing Page model.
     *
     * @return string
     * @throws MethodNotAllowedHttpException
     */
    public function actionSave()
    {
        $formModel = new <?= $formModelClass ?>();

        $formModel->setScenario($formModel::SCENARIO_SAVE);
        if ($formModel->load(Yii::$app->request->post(), '')) {
            if ($formModel->save()) {
                <?= $generator->generateSuccess(4, '\'保存成功\'') ."\n" ?>
            } elseif ($formModel->hasErrors()) {
                foreach ($formModel->errors as $field) {
                    foreach ($field as $message) {
                        <?= $generator->generateError(6, '$message') ."\n" ?>
                    }
                }
            }
        }

        throw new MethodNotAllowedHttpException();
    }

    /**
     * Deletes an existing <?= $modelClass ?> model.
     *
     * @return string
     * @throws MethodNotAllowedHttpException
     */
    public function actionDelete()
    {
        $formModel = new <?= $formModelClass ?>();

        $formModel->setScenario($formModel::SCENARIO_DELETE);
        if ($formModel->load(Yii::$app->request->post(), '')) {
            if ($formModel->delete()) {
                <?= $generator->generateSuccess(4, '\'删除成功\'') ."\n" ?>
            } elseif ($formModel->hasErrors()) {
                foreach ($formModel->errors as $field) {
                    foreach ($field as $message) {
                        <?= $generator->generateError(6, '$message') ."\n" ?>
                    }
                }
            }
        }

        throw new MethodNotAllowedHttpException();
    }
}
