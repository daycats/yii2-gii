<?php
/**
 * Created by PhpStorm.
 * User: wsl
 * Date: 2016/1/27
 * Time: 10:05
 */

namespace wsl\gii\generators\formmodel;


use Riimu\Kit\PHPEncoder\PHPEncoder;
use Yii;
use yii\base\Model;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use yii\db\Connection;
use yii\db\mysql\Schema;
use yii\gii\CodeFile;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\VarDumper;
use yii\web\Controller;

class Generator extends \yii\gii\Generator
{
    public $db = 'db';
    public $modelClass;
    public $formModelClass;
    public $baseClass = 'yii\base\Model';
    public $searchModelClass = '';
    public $controllerClass;
    public $baseControllerClass = 'yii\web\Controller';

    /**
     * @return string name of the code generator
     */
    public function getName()
    {
        return 'Form Model Generator';
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return 'This generator generates an form model class for the specified model create update delete scenario.';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            [['modelClass', 'formModelClass', 'baseClass', 'controllerClass', 'baseControllerClass'], 'filter', 'filter' => 'trim'],
            [['searchModelClass'], 'compare', 'compareAttribute' => 'modelClass', 'operator' => '!==', 'message' => 'Search Model Class must not be equal to Model Class.'],
            [['baseClass'], 'required'],
            [['modelClass', 'modelClass', 'controllerClass', 'baseControllerClass', 'searchModelClass'], 'match', 'pattern' => '/^[\w\\\\]*$/', 'message' => 'Only word characters are allowed.'],
            [['baseClass'], 'match', 'pattern' => '/^[\w\\\\]+$/', 'message' => 'Only word characters and backslashes are allowed.'],
            [['controllerClass', 'searchModelClass'], 'validateNewClass'],
            [['modelClass', 'formModelClass'], 'validateModelClass', 'skipOnEmpty' => false],
            [['baseClass', 'modelClass'], 'validateClass', 'params' => ['extends' => Model::className()]],
            [['baseControllerClass'], 'validateClass', 'params' => ['extends' => Controller::className()]],
            [['controllerClass'], function ($field) {
                if ($this->$field) {
                    if (!$this->formModelClass) {
                        $this->addError('formModelClass', 'formModelClass required');
                    }
                    if (!$this->searchModelClass) {
                        $this->addError('searchModelClass', 'searchModelClass required');
                    }
                }
            }],
        ]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(parent::attributeLabels(), [
            'modelClass' => 'Model Class',
            'formModelClass' => 'Form Model Class',
            'baseClass' => 'Base Class',
            'searchModelClass' => 'Search Model Class',
            'controllerClass' => 'Controller Class',
            'baseControllerClass' => 'Web Base Controller Class',
        ]);
    }

    /**
     * Validates the [[modelClass]] attribute.
     */
    public function validateModelClass()
    {
        if ($this->isReservedKeyword($this->modelClass)) {
            $this->addError('modelClass', 'Class name cannot be a reserved PHP keyword.');
        }
        if ((empty($this->tableName) || substr_compare($this->tableName, '*', -1, 1)) && $this->modelClass == '') {
            $this->addError('modelClass', 'Model Class cannot be blank if table name does not end with asterisk.');
        }
    }

    /**
     * @inheritdoc
     */
    public function hints()
    {
        return array_merge(parent::hints(), [
            'modelClass' => 'This is the name of the ActiveRecord class. <code>app\models\Post</code>',
            'formModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\PostForm</code>.',
            'baseClass' => 'This is the base class of the new Model class. It should be a fully qualified namespaced class name.',
            'searchModelClass' => 'This is the name of the search model class to be generated. You should provide a fully
                qualified namespaced class name, e.g., <code>app\models\PostSearch</code>.',
            'controllerClass' => 'This is the name of the controller class to be generated. You should
                provide a fully qualified namespaced class (e.g. <code>app\controllers\PostController</code>),
                and class name should be in CamelCase with an uppercase first letter. Make sure the class
                is using the same namespace as specified by your application\'s controllerNamespace property.',
            'baseControllerClass' => 'This is the class that the new CRUD controller class will extend from.
                You should provide a fully qualified class name, e.g., <code>yii\web\Controller</code>.',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function stickyAttributes()
    {
        return array_merge(parent::stickyAttributes(), ['baseClass', 'baseControllerClass']);
    }

    /**
     * Generates the code based on the current user input and the specified code template files.
     * This is the main method that child classes should implement.
     * Please refer to [[\yii\gii\generators\controller\Generator::generate()]] as an example
     * on how to implement this method.
     * @return CodeFile[] a list of code files to be created.
     */
    public function generate()
    {
        $files = [];

        if ($this->formModelClass) {
            $files[] = $this->generateFormModelCodeFile();
        }
        if ($this->searchModelClass) {
            $files[] = $this->generateSearchModelCodeFile();
        }
        if ($this->controllerClass) {
            $files[] = $this->generateControllerClassCodeFile();
        }

        return $files;
    }

    /**
     * 生成表单模型代码文件
     *
     * @return CodeFile
     * @throws \yii\base\InvalidConfigException
     */
    protected function generateFormModelCodeFile()
    {
        /** @var \yii\db\ActiveRecord $model */
        $model = new $this->modelClass;
        $encoder = new PHPEncoder();
        $modelClassName = StringHelper::basename($this->modelClass);
        $tableSchema = $model->getTableSchema();
        $pks = $model->primaryKey();
        $rules = $model->rules();

        $labelsCode = $encoder->encode($model->attributeLabels(), [
            'string.escape' => false,
            'array.base' => 8,
            'array.inline' => 120,
        ]);

        $integerFields = [];
        $stringFields = [];
        $createFields = [];
        $updateFields = [];
        $updateStatusFields = [];
        $saveFields = [];
        $deleteFields = [];
        foreach ($tableSchema->columns as $name => $column) {
            $updateFields[] = $name;
            $saveFields[] = $name;
            if (in_array($name, $pks)) {
                $updateStatusFields[] = Inflector::pluralize($name);
                $deleteFields[] = Inflector::pluralize($name);
            } else {
                $createFields[] = $name;
            }
            if ('integer' == $column->phpType || 'integer' == $column->type) {
                $integerFields[] = $name;
            }
            if ('integer' != $column->phpType && 'integer' != $column->type) {
                $stringFields[] = $name;
            }
        }
        if (isset($tableSchema->columns['status'])) {
            $updateStatusFields[] = 'status';
        }
        $createFieldsCode = $encoder->encode($createFields, [
            'string.escape' => false,
            'array.base' => 12,
            'array.inline' => 120,
        ]);
        $updateFieldsCode = $encoder->encode($updateFields, [
            'string.escape' => false,
            'array.base' => 12,
            'array.inline' => 120,
        ]);
        $updateStatusFieldsCode = $encoder->encode($updateStatusFields, [
            'string.escape' => false,
            'array.base' => 12,
            'array.inline' => 120,
        ]);
        $saveFieldsCode = $encoder->encode($saveFields, [
            'string.escape' => false,
            'array.base' => 12,
            'array.inline' => 120,
        ]);
        $deleteFieldsCode = $encoder->encode($deleteFields, [
            'string.escape' => false,
            'array.base' => 12,
            'array.inline' => 120,
        ]);

        // 表单模型目录是否存在数据模型类
        $currentFormModelFullClass = StringHelper::dirname($this->formModelClass) . '\\' . StringHelper::basename($this->modelClass);
        $isFormDirExistModel = class_exists($currentFormModelFullClass);

        $pluralizePks = [];
        foreach ($tableSchema->primaryKey as $primaryKey) {
            $pluralizePks[] = Inflector::pluralize($primaryKey);
        }

        $params = [
            'rules' => $this->generateRules($tableSchema),
            'modelClass' => $this->modelClass,
            'modelClassName' => $modelClassName,
            'tableSchema' => $tableSchema,
            'pks' => $pks,
            'labelsCode' => $labelsCode,
            'formModelClass' => $this->formModelClass,
            'createFieldsCode' => $createFieldsCode,
            'updateFieldsCode' => $updateFieldsCode,
            'updateStatusFieldsCode' => $updateStatusFieldsCode,
            'saveFieldsCode' => $saveFieldsCode,
            'deleteFieldsCode' => $deleteFieldsCode,
            'isFormDirExistModel' => $isFormDirExistModel,
            'pluralizePks' => $pluralizePks,
        ];
        $formModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->formModelClass, '\\') . '.php'));

        return new CodeFile($formModel, $this->render('model.php', $params));
    }

    /**
     * 生成控制器代码文件
     *
     * @return CodeFile
     */
    protected function generateControllerClassCodeFile()
    {
        $controllerFile = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->controllerClass, '\\')) . '.php');

        return new CodeFile($controllerFile, $this->render('controller.php'));
    }

    /**
     * Generates validation rules for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated validation rules
     */
    public function generateRules($table)
    {
        $types = [];
        $lengths = [];
        $defaults = [];
        foreach ($table->columns as $column) {
            if ($column->autoIncrement) {
                continue;
            }
            if (!$column->allowNull && $column->defaultValue === null) {
                $types['required'][] = $column->name;
            }
            if (!$column->allowNull && !is_null($column->defaultValue)) {
                $defaults[$column->defaultValue][] = $column->name;
            }
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case 'double': // Schema::TYPE_DOUBLE, which is available since Yii 2.0.3
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $types['safe'][] = $column->name;
                    break;
                default: // strings
                    if ($column->size > 0) {
                        $lengths[$column->size][] = $column->name;
                    } else {
                        $types['string'][] = $column->name;
                    }
            }
        }
        $pluralizePks = [];
        if ($table->primaryKey) {
            foreach ($table->primaryKey as $primaryKey) {
                $pluralizePks[] = Inflector::pluralize($primaryKey);
            }
        }
        $rules = [
            "[['" . implode("', '", $pluralizePks) . "'], 'required', 'on' => [static::SCENARIO_UPDATE_STATUS, static::SCENARIO_DELETE]]"
        ];
        $rules[] = "[['" . implode("', '", $table->primaryKey) . "'], 'required', 'on' => [static::SCENARIO_UPDATE]]";
        foreach ($types as $type => $columns) {
            if ('required' == $type) {
                $rules[] = "[['" . implode("', '", $columns) . "'], '$type', 'on' => [static::SCENARIO_CREATE, static::SCENARIO_UPDATE, static::SCENARIO_SAVE]]";
            } else {
                $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
            }
        }
        foreach ($lengths as $length => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], 'string', 'max' => $length]";
        }
        foreach ($defaults as $value => $columns) {
            $value = (empty($value) && $value !== 0) ? "''" : $value;
            $rules[] = "[['" . implode("', '", $columns) . "'], 'default', 'value' => $value]";
        }
        if ($pluralizePks) {
            $rules[] = "[['" . implode("', '", $pluralizePks) . "'], 'each', 'rule' => ['integer']]";
        }

        // Unique indexes rules
        try {
            $db = $this->getDbConnection();
            $uniqueIndexes = $db->getSchema()->findUniqueIndexes($table);
            foreach ($uniqueIndexes as $uniqueColumns) {
                // Avoid validating auto incremental columns
                if (!$this->isColumnAutoIncremental($table, $uniqueColumns)) {
                    $attributesCount = count($uniqueColumns);

                    if ($attributesCount == 1) {
                        $rules[] = "[['" . $uniqueColumns[0] . "'], 'unique']";
                    } elseif ($attributesCount > 1) {
                        $labels = array_intersect_key($this->generateLabels($table), array_flip($uniqueColumns));
                        $lastLabel = array_pop($labels);
                        $columnsList = implode("', '", $uniqueColumns);
                        $rules[] = "[['" . $columnsList . "'], 'unique', 'targetAttribute' => ['" . $columnsList . "'], 'message' => 'The combination of " . implode(', ', $labels) . " and " . $lastLabel . " has already been taken.']";
                    }
                }
            }
        } catch (NotSupportedException $e) {
            // doesn't support unique indexes information...do nothing
        }

        return $rules;
    }

    /**
     * 生成搜索模型代码文件
     *
     * @return CodeFile
     * @throws \yii\base\InvalidConfigException
     */
    protected function generateSearchModelCodeFile()
    {
        $currentFormModelFullClass = StringHelper::dirname($this->searchModelClass) . '\\' . StringHelper::basename($this->modelClass);
        $isFormDirExistModel = class_exists($currentFormModelFullClass);

        $searchModel = Yii::getAlias('@' . str_replace('\\', '/', ltrim($this->searchModelClass, '\\') . '.php'));

        return new CodeFile($searchModel, $this->render('search.php', [
            'isFormDirExistModel' => $isFormDirExistModel,
        ]));
    }

    /**
     * Generates validation rules for the search model.
     * @return array the generated validation rules
     */
    public function generateSearchRules()
    {
        if (($table = $this->getTableSchema()) === false) {
            return ["[['" . implode("', '", $this->getColumnNames()) . "'], 'safe']"];
        }
        $types = [];
        foreach ($table->columns as $column) {
            switch ($column->type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                    $types['integer'][] = $column->name;
                    break;
                case Schema::TYPE_BOOLEAN:
                    $types['boolean'][] = $column->name;
                    break;
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                    $types['number'][] = $column->name;
                    break;
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                default:
                    $types['safe'][] = $column->name;
                    break;
            }
        }

        $rules = [];
        foreach ($types as $type => $columns) {
            $rules[] = "[['" . implode("', '", $columns) . "'], '$type']";
        }

        return $rules;
    }

    /**
     * Generates the attribute labels for the specified table.
     * @param \yii\db\TableSchema $table the table schema
     * @return array the generated attribute labels (name => label)
     */
    public function generateLabels($table)
    {
        $labels = [];
        foreach ($table->columns as $column) {
            if (!empty($column->comment)) {
                $labels[$column->name] = $column->comment;
            } elseif (!strcasecmp($column->name, 'id')) {
                $labels[$column->name] = 'ID';
            } else {
                $label = Inflector::camel2words($column->name);
                if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                    $label = substr($label, 0, -3) . ' ID';
                }
                $labels[$column->name] = $label;
            }
        }

        return $labels;
    }

    /**
     * @return array searchable attributes
     */
    public function getSearchAttributes()
    {
        return $this->getColumnNames();
    }

    /**
     * Generates the attribute labels for the search model.
     * @return array the generated attribute labels (name => label)
     */
    public function generateSearchLabels()
    {
        /* @var $model \yii\base\Model */
        $model = new $this->modelClass();
        $attributeLabels = $model->attributeLabels();
        $labels = [];
        foreach ($this->getColumnNames() as $name) {
            if (isset($attributeLabels[$name])) {
                $labels[$name] = $attributeLabels[$name];
            } else {
                if (!strcasecmp($name, 'id')) {
                    $labels[$name] = 'ID';
                } else {
                    $label = Inflector::camel2words($name);
                    if (!empty($label) && substr_compare($label, ' id', -3, 3, true) === 0) {
                        $label = substr($label, 0, -3) . ' ID';
                    }
                    $labels[$name] = $label;
                }
            }
        }

        return $labels;
    }

    /**
     * Generates search conditions
     * @return array
     */
    public function generateSearchConditions()
    {
        $columns = [];
        if (($table = $this->getTableSchema()) === false) {
            $class = $this->modelClass;
            /* @var $model \yii\base\Model */
            $model = new $class();
            foreach ($model->attributes() as $attribute) {
                $columns[$attribute] = 'unknown';
            }
        } else {
            foreach ($table->columns as $column) {
                $columns[$column->name] = $column->type;
            }
        }

        $likeConditions = [];
        $hashConditions = [];
        foreach ($columns as $column => $type) {
            switch ($type) {
                case Schema::TYPE_SMALLINT:
                case Schema::TYPE_INTEGER:
                case Schema::TYPE_BIGINT:
                case Schema::TYPE_BOOLEAN:
                case Schema::TYPE_FLOAT:
                case Schema::TYPE_DOUBLE:
                case Schema::TYPE_DECIMAL:
                case Schema::TYPE_MONEY:
                case Schema::TYPE_DATE:
                case Schema::TYPE_TIME:
                case Schema::TYPE_DATETIME:
                case Schema::TYPE_TIMESTAMP:
                    $hashConditions[] = "'{$column}' => \$this->{$column},";
                    break;
                default:
                    $likeConditions[] = "->andFilterWhere(['like', '{$column}', \$this->{$column}])";
                    break;
            }
        }

        $conditions = [];
        if (!empty($hashConditions)) {
            $conditions[] = "\$query->andFilterWhere([\n"
                . str_repeat(' ', 12) . implode("\n" . str_repeat(' ', 12), $hashConditions)
                . "\n" . str_repeat(' ', 8) . "]);\n";
        }
        if (!empty($likeConditions)) {
            $conditions[] = "\$query" . implode("\n" . str_repeat(' ', 12), $likeConditions) . ";\n";
        }

        return $conditions;
    }

    /**
     * 响应json数据
     *
     * @param int $baseTab tab数
     * @param array $data 输出json数据
     * @return array
     */
    public function generateResponseJson($baseTab = 2, $data)
    {
        $encoder = new PHPEncoder();
        $dataCode = $encoder->encode($data, [
            'string.escape' => false,
            'array.base' => $baseTab * 4,
            'array.inline' => 120,
        ]);
        $dataCode = str_replace('\'$data\'', '$data', $dataCode);
        $dataCode = str_replace('\'$message\'', '$message', $dataCode);
        $space = str_repeat(' ', $baseTab * 4);
        $code = '$response = Yii::$app->response;
' . $space . '$response->format = Response::FORMAT_JSON;
' . $space . '$response->data = ' . $dataCode . ';
' . $space . 'return $response;';

        return $code;
    }

    /**
     * 输出消息
     *
     * @param int $baseTab tab数
     * @param int $code 返回码
     * @param string $msg 消息
     * @param array|null $data 数据
     * @param array $extraData 额外的数据只覆盖对应的key
     * @return array
     */
    public function generateOutput($baseTab = 2, $code, $msg = null, $data = null, $extraData = [])
    {
        if (is_null($msg)) {
            $msg = '';
        }
        $msg = trim($msg, '\'');
        $return = [
            'success' => (boolean)$code,
            'msg' => $msg,
        ];
        if ($extraData) {
            $return = array_merge($return, $extraData);
        }
        if (!is_null($data)) {
            $return['data'] = $data;
        }

        return $this->generateResponseJson($baseTab, $return);
    }

    /**
     * 成功信息
     *
     * @param int $baseTab tab数
     * @param string $msg 消息
     * @param string $data 数据
     * @param string $extraData 额外的数据只覆盖对应的key
     * @return array
     */
    public function generateSuccess($baseTab, $msg, $data = null, $extraData = null)
    {
        $baseController = $this->getBaseControllerInstance();
        if (method_exists($baseController, 'success')) {
            $params = [$msg];
            if (!is_null($data)) {
                $params[] = $data;
            }
            if (!is_null($extraData)) {
                $params[] = $extraData;
            }
            return 'return $this->success(' . join(',', $params) . ');';
        } else {
            return $this->generateOutput($baseTab, 1, $msg, $data, $extraData);
        }
    }

    /**
     * 失败信息
     *
     * @param int $baseTab tab数
     * @param string $msg 消息
     * @param string $data 数据
     * @param string $extraData 额外的数据只覆盖对应的key
     * @return array
     */
    public function generateError($baseTab, $msg, $data = null, $extraData = null)
    {
        $baseController = $this->getBaseControllerInstance();
        if (method_exists($baseController, 'success')) {
            $params = [$msg];
            if (!is_null($data)) {
                $params[] = $data;
            }
            if (!is_null($extraData)) {
                $params[] = $extraData;
            }
            return 'return $this->error(' . join(',', $params) . ');';
        } else {
            return $this->generateOutput($baseTab, 0, $msg, $data, $extraData);
        }
    }

    /**
     * 输出数据
     *
     * @param int $baseTab tab数
     * @param string $msg 消息
     * @param string $data 数据
     * @param string $extraData 额外的数据只覆盖对应的key
     * @return array
     */
    public function generateSuccessData($baseTab, $data, $msg = null, $extraData = null)
    {
        $baseController = $this->getBaseControllerInstance();
        if (method_exists($baseController, 'success')) {
            $params = [$data];
            if (!is_null($msg)) {
                $params[] = $msg;
            }
            if (!is_null($extraData)) {
                $params[] = $extraData;
            }
            return 'return $this->successData(' . join(',', $params) . ');';
        } else {
            return $this->generateOutput($baseTab, 1, $msg, $data, $extraData);
        }
    }

    /**
     * Generates action parameters
     *
     * @param string $prefix Prefix
     * @param string $delimiter Delimiter
     * @return string
     */
    public function generateActionParams($prefix = '$', $delimiter = ', $')
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (count($pks) === 1) {
            return $prefix . ArrayHelper::getValue($pks, 0);
        } else {
            return $prefix . implode($delimiter, $pks);
        }
    }

    /**
     * Generates parameter tags for phpdoc
     * @return array parameter tags for phpdoc
     */
    public function generateActionParamComments()
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        $pks = $class::primaryKey();
        if (($table = $this->getTableSchema()) === false) {
            $params = [];
            foreach ($pks as $pk) {
                $params[] = '@param ' . (substr(strtolower($pk), -2) == 'id' ? 'integer' : 'string') . ' $' . $pk;
            }

            return $params;
        }
        if (count($pks) === 1) {
            $type = $table->columns[$pks[0]]->type;
            $phpType = $table->columns[$pks[0]]->phpType;
            return ['@param ' . ('integer' == $type || 'integer' == $phpType ? 'integer' : 'string') . ' $' . ArrayHelper::getValue($pks, 0)];
        } else {
            $params = [];
            foreach ($pks as $pk) {
                $type = $table->columns[$pk]->type;
                $phpType = $table->columns[$pk]->phpType;
                $params[] = '@param ' . ('integer' == $type || 'integer' == $phpType ? 'integer' : 'string') . ' $' . $pk;
            }

            return $params;
        }
    }

    private $_baseController;

    public function getBaseControllerInstance()
    {
        if (!$this->_baseController) {
            $this->_baseController = new $this->baseControllerClass(null, null);
        }

        return $this->_baseController;
    }

    /**
     * Returns table schema for current model class or false if it is not an active record
     * @return \yii\db\Schema
     */
    public function getTableSchema()
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema();
        } else {
            return false;
        }
    }

    /**
     * @return array model column names
     */
    public function getColumnNames()
    {
        /* @var $class ActiveRecord */
        $class = $this->modelClass;
        if (is_subclass_of($class, 'yii\db\ActiveRecord')) {
            return $class::getTableSchema()->getColumnNames();
        } else {
            /* @var $model \yii\base\Model */
            $model = new $class();

            return $model->attributes();
        }
    }

    /**
     * @return Connection the DB connection as specified by [[db]].
     */
    protected function getDbConnection()
    {
        return Yii::$app->get($this->db, false);
    }

    /**
     * Checks if any of the specified columns is auto incremental.
     * @param \yii\db\TableSchema $table the table schema
     * @param array $columns columns to check for autoIncrement property
     * @return boolean whether any of the specified columns is auto incremental.
     */
    protected function isColumnAutoIncremental($table, $columns)
    {
        foreach ($columns as $column) {
            if (isset($table->columns[$column]) && $table->columns[$column]->autoIncrement) {
                return true;
            }
        }

        return false;
    }
}