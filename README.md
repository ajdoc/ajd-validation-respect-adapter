# AJD Validation respect adapter 

Respect Validation adapter for AJD validation

## Description

Respect Validation adapter for AJD validation so that you could use repect validation's rules with the unique features that ajd validation provides.

## Getting Started

### Installing
* composer require ajd/ajd-validation-respect-adapter
* after installing add the package to AJD validation by
* adding package is usally done in the bootsraping of your application
```php
use AJD_validation\AJD_validation;

$v->addPackages([
	AjdRespect\AjdRespectServiceProvider::class
]);
```

## Documentation

### API
- After installing and adding the package you now have access to respect validation rules
- To access respect validation rules prefix the respect validation rule with `respect[respect_rule_name]`
```php
use AJD_validation\AJD_validation;

$v = new AJD_validation;

$v
	->respectnotempty()
	->respectalnum('*')
	->check('respectrules', '');
// outputs error 
	/*
		All of the required rules must pass for "Respectrules".
		  - Respectrules must not be empty
		  - Respectrules must contain only letters (a-z), digits (0-9) and "*"
	*/

$v
	->getValidator()
	->respectnotempty()
	->respectalnum('*')
	->validate(''); // returns false

$v
	->respectnotempty()
	->respectalnum('*')
	->check('respectrules', 'a'); // validation passes

$v
	->getValidator()
	->respectnotempty()
	->respectalnum('*')
	->validate('a'); // returns true
```
- if you are going to use special rules of respect validation which requries Respect's \Respect\Validation\Validatable as a satisfier/argument
	1. Use `$v->getRespectValidator()` (this method comes with the package) method which returns \Respect\Validation\Validator instance.
	2. Then enclosed all the \Respect\Validation\Validatable in an array.
	3. When using `$v->getRespectValidator()` you must call respect validation rules like as discussed here:  
		[Respect validation documentation](https://respect-validation.readthedocs.io/en/latest/)

```php
use AJD_validation\AJD_validation;

$v = new AJD_validation;

$v
	->respectnoneof(
		[
			$v->getRespectValidator()
				->NotEmpty()
				->alnum('*')
		]
	)
	->check('respectrules', 'a');
// outputs error 
	/*
		All of the required rules must pass for "Respectrules".
  			- None of these rules must pass for Respectrules
	*/

$v
	->getValidator()
	->respectnoneof(
		[
			$v->getRespectValidator()
				->NotEmpty()
				->alnum('*')
		]
	)
	->validate('a'); // returns false

$v
	->respectnoneof(
		[
			$v->getRespectValidator()
				->NotEmpty()
				->alnum('*')
		]
	)
	->check('respectrules', ''); // validation passes

$v
	->getValidator()
	->respectnoneof(
		[
			$v->getRespectValidator()
				->NotEmpty()
				->alnum('*')
		]
	)
	->validate(''); // returns true

```

See respect validation documentation here 
	- [Respect validation documentation](https://respect-validation.readthedocs.io/en/latest/)


## Authors
Contributors names and contact info
Aj Doc (thedoctorisin17@gmail.com)  

## Version History

* 0.1 (master)
    * Initial Release


## Links
* See also:
	- [AJD validation](https://github.com/ajdoc/ajd-validation)

## Acknowledgments
Inspiration, code snippets, etc.
* [respect/validation](https://github.com/Respect/Validation)
	