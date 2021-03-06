<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\FloatType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;

class IsNumericFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	/** @var \PHPStan\Analyser\TypeSpecifier */
	private $typeSpecifier;

	public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context): bool
	{
		return $functionReflection->getName() === 'is_numeric'
			&& isset($node->args[0])
			&& !$context->null();
	}

	public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$argType = $scope->getType($node->args[0]->value);
		if ($context->truthy() && !(new StringType())->isSuperTypeOf($argType)->no() && !$argType instanceof MixedType) {
			return new SpecifiedTypes([], []);
		}

		$numericTypes = [
			new IntegerType(),
			new FloatType(),
		];

		if ($context->truthy()) {
			$numericTypes[] = new StringType();
		}

		return $this->typeSpecifier->create($node->args[0]->value, new UnionType($numericTypes), $context);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
