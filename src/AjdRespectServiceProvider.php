<?php
namespace AjdRespect;

use AJD_validation\Contracts\Validation_provider;
use AJD_validation\AJD_validation;
use AJD_validation\Contracts\Validator;
use AJD_validation\Contracts\Abstract_anonymous_rule;
use AJD_validation\Contracts\Abstract_exceptions;
use AjdRespect\Macros\AjdRespectMacro;

class AjdRespectServiceProvider extends Validation_provider
{
	const DS = DIRECTORY_SEPARATOR;
	const RESPECT_NAMESPACE = 'Respect\\Validation\\';
	protected $respectRulesNamespace = self::RESPECT_NAMESPACE.'Rules\\';
	protected $respectExceptionsNamespace = self::RESPECT_NAMESPACE.'Exceptions\\';
	protected $respectSignature = 'respect';

	/**
     * Registers this packages custom rules -> exceptions, macros, logics, filters, validations, extensions.
     *
     * @return void
     */
	public function register()
	{
		$this
			->setDefaults([
				'baseDir' => __DIR__,
				'baseNamespace' => __NAMESPACE__
			])
			->mixin(AjdRespectMacro::class)
			->tryGetRespectRules();
	}

	/**
     * Try and registers and create adapter for all the rules -> exceptions for repect validation.
     *
     * @return self
     */
	protected function tryGetRespectRules()
	{
		$baseRepectDir = dirname(__DIR__).self::DS.'vendor'.self::DS.'respect'.self::DS.'validation'.self::DS.'library'.self::DS;
		$rulesDir = $baseRepectDir.'Rules';
		$exceptionsDir = $baseRepectDir.'Exceptions';

		$validator = new Validator;
		$checkFile = $validator->contains('Abstract');

		$filterFile = function($filename) use($checkFile)
		{
			return !$checkFile->validate($filename);
		};

		$rules = array_map([$this, 'createClass'], array_diff(scandir($rulesDir), ['.', '..']));
        $rules = array_filter($rules, $filterFile);

        $exceptions = array_map([$this, 'createClass'], array_diff(scandir($exceptionsDir), ['.', '..']));
        $exceptions = array_filter($exceptions, $filterFile);

        $this->createMapping($rules, $exceptions, $rulesDir, $exceptionsDir, true);

        return $this;
	}

	/**
     * Remove .php extension of filename.
     *
     * @param  string  $filename
     * @return string
     */
	protected function createClass($filename)
	{
		return substr($filename, 0, -4);
	}

	/**
     * Create mapping or register respect validation rules as anonymous class for ajd validation.
     *
     * @param  array  $rules
     * @param  array  $exceptions
     * @param  string  $rulesDir
     * @param  string  $exceptionsDir
     * @param  bool  $anonymousWay
     * @return void|array
     */
	protected function createMapping(array $rules, array $exceptions, $rulesDir, $exceptionsDir, $anonymousWay = true )
	{	
		$maps = [];
		if(!empty($rules) && !empty($exceptions))
		{
			foreach($rules as $rule)
			{
				$exception = $rule.'Exception';
				$ruleClass = $this->respectRulesNamespace.$rule;

				if(in_array($exception, $exceptions))
				{
					$exceptionClass = $this->respectExceptionsNamespace.$exception;

					if(class_exists($ruleClass) && class_exists($exceptionClass))
					{						
						$signature = $this->respectSignature.mb_strtolower($rule);

						if($anonymousWay)
						{
							$this->registerRespect($signature, $ruleClass, $exceptionClass, $rulesDir, $exceptionsDir);
						}
						else
						{
							$maps[$signature][$ruleClass] = $exceptionClass;
						}
					}
				}
			}
		}

		return $maps;
	}

	/**
     * Registers all respect validation rules as anonymous class for ajd validation.
     *
     * @param  string  $ruleName
     * @param  string  $ruleClass
     * @param  string  $exceptionClass
     * @param  string  $rulesDir
     * @param  string  $exceptionsDir
     * @return void
     */
	public function registerRespect($ruleName, $ruleClass, $exceptionClass, $rulesDir, $exceptionsDir)
	{
		if(!AJD_validation::hasAnonymousClass($ruleName))
        {
        	$that = $this;

			$anonClassRule = new class($ruleClass, $that, $rulesDir) extends Abstract_anonymous_rule
			{
				public $ruleClass;
				protected static $exceptionClass;
				protected $mainObject;
				protected $rulesDir;
				public $exception;

				public function __construct($ruleClass, $mainObject, $rulesDir)
				{
					$this->ruleClass = $ruleClass;
					$this->mainObject = $mainObject;
					$this->rulesDir = $rulesDir;
				}

				public function __invoke($value, $satisfier = null, $field = null, $clean_field = null, $origValue = null, $inverse = false)
				{	
					if(isset($satisfier[0]))
					{
						if(is_array($satisfier[0]))
						{
							$satisfier = $satisfier[0];
						}
					}

					$obj = $this->processRespect($this->ruleClass, $satisfier, $inverse);

					if(empty($obj))
					{
						return false;
					}

					$defaultMessage = 'Repect Rule '.$this->ruleClass.' error.';

					if($inverse)
					{
						$defaultMessage = 'Repect Rule '.$this->ruleClass.' inverse error.';						
					}

					if(!is_null($this->inverseCheck))
					{
						if($this->inverseCheck === true)
						{
							$inverse = true;
						}
					}

					try
					{
						$realObj = $obj;

						if($inverse)
						{
							$v = new \Respect\Validation\Validator;

							$realObj = $v->not($obj);
						}

						$realField = !empty($clean_field) ? $clean_field : $field;

						if(!empty($realField))
						{
							$realObj->setName($realField);
						}

						$realObj->assert($value);

					}
					catch(\Respect\Validation\Exceptions\NestedValidationException $e)
					{
						$messages = $e->getMessages();
						$firstKey = array_key_first($messages);
						$this->exception = $messages[$firstKey] ?? $defaultMessage;
					}
					catch(\Respect\Validation\Exceptions\ValidationException $e)
					{
						$this->exception = $e->getMessage();
					}

					if(!empty($this->exception))
					{
						return $inverse ? true : false;
					}

					return $inverse ? false : true;
				}

				protected function processRespect($class, $satisfier = null, $inverse = false)
				{
					$args = [];
						
					if(is_array($satisfier))
					{
						foreach($satisfier as $s)
						{
							if(!empty($s))
							{
								$args[] = $s;
							}
						}
					}
					else
					{
						if(!empty($satisfier))
						{
							$args = [$satisfier];	
						}
						
					}
					
					$reflect = new \ReflectionClass( $class );
					$getConstructor = $reflect->getConstructor();

					$newObj = (bool) $getConstructor ? $reflect->newInstanceArgs( $args ) : $reflect->newInstanceWithoutConstructor();
					
					return $newObj;
				}

				public static function getAnonName() : string
				{
					return static::$setAnonName;
				}

				public static function getAnonExceptionMessage(Abstract_exceptions $exceptionObj, $anon = null)
				{
					$defaultMessage = 'Repect Rule '.$anon->ruleClass.' error.';
					$inverseMessage = 'Repect Rule '.$anon->ruleClass.' inverse error.';

					if(!empty($anon->exception))
					{
						$defaultMessage = $anon->exception;
						$inverseMessage = $defaultMessage;
					}
					
					$exceptionObj::$defaultMessages 	= [
						 $exceptionObj::ERR_DEFAULT 			=> [
						 	$exceptionObj::STANDARD 			=> $defaultMessage,
						 ],
					  	 $exceptionObj::ERR_NEGATIVE 		=> [
				            $exceptionObj::STANDARD 			=> $inverseMessage,
				        ]
					];
				}

				public static function setAnonName($ruleName)
				{
					static::$setAnonName = $ruleName;
				}

				public static function setExceptionClass($exceptionClass)
				{
					static::$exceptionClass = $exceptionClass;
				}
			};

			$anonClassRule::setExceptionClass($ruleClass);
			$anonClassRule::setAnonName($ruleName);

			AJD_validation::registerAnonClass($anonClassRule);
		}
	}
}