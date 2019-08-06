<?php

require_once '../vendor/autoload.php';

/**
 * 抽取类的骨架
 * 类似将一个类抽取成接口定义的形式
 * Class Extractor
 */
class Extractor
{
    // 组装好的模板
    protected $exportTemplate;

    /**
     * 导出一个类
     * @param $fullClassName
     * @throws ReflectionException
     */
    function export($fullClassName)
    {
        // 反射获取类的定义
        $refClass = new ReflectionClass($fullClassName);

        // 抽取类的成员定义
        foreach ($refClass->getProperties() as $reflectionProperty) {
            // 成员名和声明
            $name = $reflectionProperty->getName();
            $definition = implode(' ', array_filter([
                $reflectionProperty->getDocComment() . PHP_EOL,
                $reflectionProperty->isPublic() ? 'public' : false,
                $reflectionProperty->isPrivate() ? 'private' : false,
                $reflectionProperty->isProtected() ? 'protected' : false,
                $reflectionProperty->isStatic() ? 'static' : false,
                '$' . $name
            ]));

            // 是否附带有默认值
            $defaultValue = $refClass->getDefaultProperties()[$name] !== NULL;
            if ($defaultValue) {
                $definition .= '=' . var_export($refClass->getDefaultProperties()[$name], true);
            }
            $this->exportTemplate .= "{$definition};";
        }

        // 抽取类的方法定义
        foreach ($refClass->getMethods() as $method) {

            // 方法名称和前置声明
            $name = $method->getName();
            $definition = implode(' ', array_filter([
                $method->isFinal() ? 'final' : false,
                $method->isAbstract() ? 'abstract' : false,
                $method->isPublic() ? 'public' : false,
                $method->isPrivate() ? 'private' : false,
                $method->isProtected() ? 'protected' : false,
                $method->isStatic() ? 'static' : false,
                'function'
            ]));

            // 拆解方法参数
            $parameters = '';
            foreach ($method->getParameters() as $parameter) {
                $default = '';
                $defaultValue = $parameter->isDefaultValueAvailable(); // 参数是否具有默认值
                if ($defaultValue) {
                    $realValue = $parameter->isDefaultValueConstant() ? $parameter->getDefaultValue() : $parameter->getDefaultValueConstantName();
                    $default = "=" . var_export($realValue, true);
                }
                $paramType = '';
                $paramTypeDef = $parameter->getType();
                if ($paramTypeDef instanceof ReflectionNamedType) {
                    $paramType .= $parameter->getType()->allowsNull() ? '? ' : '';
                    $paramType .= $parameter->getType()->isBuiltin() ? '' : '\\';
                    $paramType .= $parameter->getType()->getName();
                }
                $parameters .= "{$paramType} \${$parameter->getName()}{$default},";
            }
            $parameters = rtrim($parameters, ',');

            // 返回类型限定(PHP7)
            $returnType = '';
            if ($method->getReturnType() instanceof ReflectionNamedType) {
                $returnType .= ':';
                $returnType .= $method->getReturnType()->allowsNull() ? '? ' : '';
                $returnType .= $method->getReturnType()->isBuiltin() ? '' : '\\';
                $returnType .= $method->getReturnType()->getName();
            }

            // 方法的PHPDoc
            $doc = $method->getDocComment();
            $this->exportTemplate .= "{$doc}\n{$definition} {$name}({$parameters}){$returnType} {}\n";
            file_put_contents($refClass->getShortName() . '.refClass', $this->exportTemplate);
        }
    }
}

(new Extractor)->export(\think\db\Connection::class);