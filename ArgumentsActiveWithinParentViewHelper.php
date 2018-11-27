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
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * ViewHelper to get additional params including add/remove categories from list
 *
 * # Example: Basic Example
 * # Description: Render the content of the VH as page title
 * <code>
 *    <f:link.page title="{category.item.title}" pageUid="{settings.listPid}"
 *       additionalParams="{n:multiCategoryLink.arguments(
 *          mode:'add',
 *          item:category.item.uid,
 *          list:overwriteDemand.categories)}">link
 *     </f:link.page>
 * </code>
 * <output>
 *    <title>TYPO3 is awesome</title>
 * </output>
 *
 */
class ArgumentsActiveWithinParentViewHelper extends AbstractViewHelper implements CompilableInterface
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('mode', 'string', 'Mode, either "add" or "remove"', true);
        $this->registerArgument('list', 'string', 'Category list', false, '');
        $this->registerArgument('item', 'int', 'Category id', true);
        $this->registerArgument('parent', 'int', 'Parent category id', true);
        $this->registerArgument('additionalParams', 'array', 'Additional params', false, []);
        parent::initializeArguments();
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    )
    {
        if ($arguments['mode'] !== 'add' && $arguments['mode'] !== 'remove') {
            throw new Exception('Mode must be either "add" or "remove', 1522293549);
        }

        $allArguments = (array)$arguments['additionalParams'];
        $categoryId = $arguments['item'];
        $parentCategoryId = (int)$arguments['parent'];

        $categoryList = $arguments['list'];
        if ($arguments['mode'] === 'add') {
            if (!GeneralUtility::inList($categoryList, $categoryId)) {
                $categoryList .= ',' . $categoryId;
            }
        } else {

            // remove
            if (GeneralUtility::inList($categoryList, $categoryId)) {
                $categoryList = GeneralUtility::rmFromList($categoryId, $categoryList);
            } else {
                // replace
                $all = self::getIdListOfParent($parentCategoryId);
                foreach ($all as $itToBeRemoved) {
                    $categoryList = GeneralUtility::rmFromList($itToBeRemoved, $categoryList);
                }
                $categoryList .= ',' . $categoryId;
            }
        }

        if (!empty($categoryList)) {
            $categoryList = trim($categoryList, ',');
            $categoryArray = [
                'tx_news_pi1' => [
                    'overwriteDemand' => [
                        'categories' => $categoryList
                    ]
                ]
            ];
            ArrayUtility::mergeRecursiveWithOverrule($allArguments, $categoryArray);
        }

        return $allArguments;
    }


    protected static function getIdListOfParent(int $id): array
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

        return $idList;
    }
}