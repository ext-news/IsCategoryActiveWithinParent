<?php
declare(strict_types=1);

namespace GeorgRinger\News\ViewHelpers\MultiCategoryLink;

/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * ViewHelper to check if th
 *
 * # Example: Basic example
 * <code>
 * <n:multiCategoryLink.isCategoryActive list="{overwriteDemand.categories}" item="{category.uid}">
 * do stuff
 * </n:multiCategoryLink.isCategoryActive>
 * </code>
 * <output>
 * "du stuff" will be shown if the category is currently in demand
 * </output>
 *
 */
class IsCategoryActiveWithinParentViewHelper extends AbstractConditionViewHelper implements CompilableInterface
{
    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('list', 'string', 'List of news', false, '');
        $this->registerArgument('parent', 'int', 'Parent Category id', true);
        $this->registerArgument('item', 'int', 'Category id', true);
        parent::initializeArguments();
    }

    /**
     * @param array|null $arguments
     * @return bool
     */
    protected static function evaluateCondition($arguments = null): bool
    {
        if (empty($arguments['list'])) {
            return false;
        }
        $childLists = self::getIdListOfParent((int)$arguments['parent']);

        $anyoneFound = false;
        $split = GeneralUtility::intExplode(',', $arguments['list']);
        foreach ($split as $splitItem) {
            if (GeneralUtility::inList($childLists, $splitItem)) {
                $anyoneFound = true;
            }
        }
        return GeneralUtility::inList($arguments['list'], $arguments['item']) || $anyoneFound;
    }

    protected static function getIdListOfParent(int $id): string
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_category');
        $rows = $queryBuilder
            ->select('*')
            ->from('sys_category')
            ->where(
                $queryBuilder->expr()->eq('parent', $queryBuilder->createNamedParameter($id, \PDO::PARAM_INT))
            )
            ->execute()
            ->fetchAll();

        $idList = [];
        foreach ($rows as $row) {
            $idList[] = $row['uid'];
        }

        return implode(',', $idList);
    }

    /**
     * @return mixed
     */
    public function render()
    {
        if (static::evaluateCondition($this->arguments)) {
            return $this->renderThenChild();
        }
        return $this->renderElseChild();
    }
}
