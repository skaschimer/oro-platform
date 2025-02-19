<?php

namespace Oro\Bundle\FilterBundle\Datasource\Orm;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\FilterBundle\Datasource\ExpressionBuilderInterface;
use Oro\Bundle\FilterBundle\Expr\Coalesce;

/**
 * Filter expression builder for ORM.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class OrmExpressionBuilder implements ExpressionBuilderInterface
{
    /** @var Expr */
    protected $expr;

    /** @var bool */
    protected $caseInsensitive;

    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
        $this->caseInsensitive = false;
    }

    #[\Override]
    public function setCaseInsensitive($caseInsensitive = false)
    {
        $this->caseInsensitive = $caseInsensitive;
    }

    #[\Override]
    public function andX($_)
    {
        return call_user_func_array([$this->expr, 'andX'], func_get_args());
    }

    #[\Override]
    public function orX($_)
    {
        return call_user_func_array([$this->expr, 'orX'], func_get_args());
    }

    #[\Override]
    public function comparison($x, $operator, $y, $withParam = false)
    {
        return new Expr\Comparison($this->prepareParameter($x), $operator, $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function eq($x, $y, $withParam = false)
    {
        return $this->expr->eq($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function neq($x, $y, $withParam = false)
    {
        /*
         * The correct expression cannot be used due a bug in doctrine 2.5, in the case when we try equals expression
         * with IS NULL.
         * An example of DQL which fails:
         * SELECT u.id FROM Oro\Bundle\UserBundle\Entity\User u
         * WHERE (
         *      CASE WHEN (:business_unit_id IS NOT NULL)
         *           THEN CASE
         *                  WHEN (:business_unit_id MEMBER OF u.businessUnits OR u.id IN (:data_in)) AND
         *                        u.id NOT IN (:data_not_in)
         *                  THEN true
         *                  ELSE false
         *              END
         *      ELSE
         *      CASE
         *          WHEN u.id IN (:data_in) AND u.id NOT IN (:data_not_in)
         *          THEN true
         *          ELSE false
         *      END
         * END) IS NULL
         *
         * When it uncommented you can check that all works ok, for example, on edit business unit page,
         * just try to apply 'no' filter on users grid on this page
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->neq($x, $withParam ? ':' . $y : $y)
        );
        */

        return $this->expr->neq($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function lt($x, $y, $withParam = false)
    {
        return $this->expr->lt($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function lte($x, $y, $withParam = false)
    {
        return $this->expr->lte($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function gt($x, $y, $withParam = false)
    {
        return $this->expr->gt($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function gte($x, $y, $withParam = false)
    {
        return $this->expr->gte($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function not($restriction)
    {
        return $this->expr->not($restriction);
    }

    #[\Override]
    public function in($x, $y, $withParam = false)
    {
        return $this->expr->in($this->prepareParameter($x), $this->prepareParameter($y, $withParam, false));
    }

    public function inInv($x, $y, $withParam = false)
    {
        return $this->expr->in($this->prepareParameter($x, $withParam, false), $this->prepareParameter($y));
    }

    #[\Override]
    public function notIn($x, $y, $withParam = false)
    {
        return $this->expr->notIn($this->prepareParameter($x), $this->prepareParameter($y, $withParam, false));
    }

    #[\Override]
    public function isNull($x)
    {
        return $this->expr->isNull($x);
    }

    #[\Override]
    public function isNotNull($x)
    {
        return $this->expr->isNotNull($x);
    }

    #[\Override]
    public function like($x, $y, $withParam = false)
    {
        return $this->expr->like($this->prepareParameter($x), $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function notLike($x, $y, $withParam = false)
    {
        /*
         * The correct expression cannot be used due a bug in doctrine 2.5, in the case when we try equals expression
         * with IS NULL. See neq method.
         *
         * Also we cannot use NOT (x LIKE y) due a bug in AclHelper, so we have to use NOT LIKE operator.
         * Here is the error: Notice: Undefined property: Doctrine\ORM\Query\AST\ConditionalFactor::$conditionalTerms
         *      in C:\www\home\oro\crm-dev\src\Oro\src\Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper.php on line 119
         * The problem can be reproduced, for example, on System > Data Audit grid, just try to apply
         * 'does not contain' filer to 'author' column
         * Make sure that NOT (x LIKE y) works before use it; otherwise, use NOT LIKE
         *
        return $this->expr->orX(
            $this->isNull($x),
            $this->expr->not(
                $this->expr->like($x, $withParam ? ':' . $y : $y)
            )
        );
        */

        return new Expr\Comparison($this->prepareParameter($x), 'NOT LIKE', $this->prepareParameter($y, $withParam));
    }

    #[\Override]
    public function literal($literal)
    {
        return $this->expr->literal($literal);
    }

    #[\Override]
    public function trim($x)
    {
        return $this->expr->trim($x);
    }

    #[\Override]
    public function coalesce(array $x)
    {
        return new Coalesce($x);
    }

    public function exists($x)
    {
        return $this->expr->exists($x);
    }

    /**
     * @param $param
     * @param bool $withParam
     * @param bool $allowInsensitive
     *
     * @return mixed
     */
    protected function prepareParameter($param, $withParam = false, $allowInsensitive = true)
    {
        $param = $withParam ? ':' . $param : $param;
        if ($allowInsensitive && $this->caseInsensitive) {
            $param = $this->expr->lower($param);
        }

        return $param;
    }
}
