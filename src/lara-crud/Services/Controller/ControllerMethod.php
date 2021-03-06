<?php

namespace LaraCrud\Services\Controller;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LaraCrud\Contracts\Controller\ApiResponseMethod;
use LaraCrud\Contracts\Controller\RedirectAbleMethod;
use LaraCrud\Contracts\ViewAbleMethod;
use LaraCrud\Helpers\Helper;
use LaraCrud\Helpers\RedirectAbleMethodHelper;
use LaraCrud\Helpers\ViewAbleMethodHelper;

abstract class ControllerMethod
{
    use ViewAbleMethodHelper;
    use RedirectAbleMethodHelper;
    use Helper;

    /**
     * List of full namespaces that will be import on top of controller.
     *
     * @var array
     */
    protected $namespaces = [];

    /**
     * @var \ReflectionClass
     */
    protected $modelReflectionClass;

    /**
     * Whether its an API method or not.
     *
     * @var bool
     */
    protected $isApi = false;

    /**
     * Full Namespace Request folder where system will find custom Request class or save into it.
     *
     * @var string
     */
    protected $requestFolderNs;

    /**
     * Eloquent Model that will be as main model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * Parent Model.
     *
     * If controller has a parent. For example Comment Model may have Post parent.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $parentModel;

    /**
     * @var string
     */
    protected string $parentModelShortName;

    /**
     * @var string
     */
    protected string $modelShortName;

    /**
     * Name of the controller method.
     *
     * @var string
     */
    protected string $methodName;

    /**
     * ControllerMethod constructor.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     *
     * @throws \ReflectionException
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->modelReflectionClass = new \ReflectionClass($model);

        if ($this instanceof ApiResponseMethod) {
            $requestNs = config('laracrud.request.apiNamespace');

            $this->isApi = true;
        } else {
            $requestNs = config('laracrud.request.namespace');
        }

        $this->requestFolderNs = $this->getFullNS($requestNs).'\\'.ucfirst(Str::camel($this->model->getTable()));
    }

    /**
     * Name of of Controller Method.
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    public function getMethodName(): string
    {
        if (!empty($this->methodName)) {
            return $this->methodName;
        }
        $reflection = new \ReflectionClass(static::class);

        return $this->methodName = lcfirst($reflection->getShortName());
    }

    /**
     * Will be called before getViewGenerateCode method call to setup necessary parameters and variables.
     *
     * @return $this
     */
    protected function beforeGenerate()
    {
        return $this;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setMethodName(string $name): self
    {
        $this->methodName = $name;

        return $this;
    }

    /**
     * @throws \ReflectionException
     *
     * @return string
     */
    public function getCode(): string
    {
        if ($this instanceof ViewAbleMethod) {
            return $this->beforeGenerate()->generateViewCode();
        } elseif ($this instanceof RedirectAbleMethod) {
            return $this->beforeGenerate()->generateRedirectAbleCode();
        }
    }

    /**
     * @return array
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function getRequestClass(): string
    {
        $customRequestName = ucfirst($this->getMethodName()).config('laracrud.request.classSuffix', 'Request');
        $fullRequestNs = $this->requestFolderNs.'\\'.$customRequestName;

        if (class_exists($fullRequestNs)) {
            $requestClass = $customRequestName;
            $this->namespaces[] = $fullRequestNs;
        } else {
            $requestClass = 'Request';
        }

        return $requestClass;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $parentModel
     *
     * @return $this
     */
    public function setParent(Model $parentModel): self
    {
        $this->parentModel = $parentModel;
        $this->namespaces[] = get_class($parentModel);

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return '';
    }

    /**
     * Get Model class name without namespace.
     *
     * @return string
     */
    protected function getModelShortName(): string
    {
        if (!empty($this->modelShortName)) {
            return $this->modelShortName;
        }

        return $this->modelShortName = lcfirst($this->modelReflectionClass->getShortName());
    }

    /**
     * Get Model class Name without namespace.
     *
     * @throws \ReflectionException
     *
     * @return string
     */
    protected function getParentShortName(): string
    {
        if (!empty($this->parentModelShortName)) {
            return $this->parentModelShortName;
        }

        return $this->parentModelShortName = lcfirst((new \ReflectionClass($this->parentModel))->getShortName());
    }
}
