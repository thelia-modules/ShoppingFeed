<?php

namespace ShoppingFeed\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use ShoppingFeed\Model\Map\ShoppingfeedLogTableMap;
use ShoppingFeed\Model\Map\ShoppingfeedOrderDataTableMap;
use ShoppingFeed\Model\ShoppingfeedLogQuery;
use ShoppingFeed\Model\ShoppingfeedOrderDataQuery;
use ShoppingFeed\Service\LogService;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\HttpFoundation\JsonResponse;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Tools\URL;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin/module/ShoppingFeed/logs", name="shopping_feed_log_")
 */
class LogController extends BaseAdminController
{
    const ORDER_DATA_JOIN = "orderDataJoin";

    /**
     * @Route("/view", name="view_log")
     */
    public function viewAction(RequestStack $requestStack, LogService $logService)
    {
        $request = $requestStack->getCurrentRequest();

        $logQuery = ShoppingfeedLogQuery::create();

        $orderDataJoin = new Join(
            ShoppingfeedLogTableMap::COL_OBJECT_ID,
            ShoppingfeedOrderDataTableMap::COL_ID,
            Criteria::LEFT_JOIN
        );
        $logQuery->addJoinObject($orderDataJoin, $this::ORDER_DATA_JOIN);

        $logQuery->addJoinCondition(
            self::ORDER_DATA_JOIN,
            '('.ShoppingfeedLogTableMap::COL_OBJECT_TYPE." = 'Order')"
        );


        $this->applyOrder($requestStack, $logQuery, $logService);

        $queryCount = clone $logQuery;

        if ($request->get('filter')) {
            $this->filterByLevel($request, $logQuery);
            $this->applySearch($request, $logQuery);
        }

        $querySearchCount = clone $logQuery;

        $logQuery->offset((int) $request->get('start'));

        if ($request->get('limit')) {
            $logQuery->limit($request->get('limit'));
        }
        $logs = $logQuery->find();

        $data = [];
        foreach ($logs as $log) {
            $orderData = null;
            $extra = ['content' => '-'];
            if ($log->getObjectType() == 'Order') {
                $orderId = $log->getObjectId();
                $orderRef = $log->getObjectRef();
                if ($orderId) {
                    $orderData = ShoppingfeedOrderDataQuery::create()->filterById($orderId)->findOne();
                    $extra['url'] = URL::getInstance()->absoluteUrl('admin/order/update/'.$orderId);
                }
                if ($orderRef) {
                    $extra['content']= 'REF: '.$orderRef;
                }
            }
            if ($log->getObjectType() == 'Mapping') {
                $code = $log->getObjectRef();
                $extra['url'] = URL::getInstance()->absoluteUrl('admin/module/ShoppingFeed?current_tab=mapping');
                $extra['content'] = 'MAP ';
                if ($code) {
                    $extra['content'] .= $code;
                }
            }
            // For PSE ObjectRef is the product ID
            if ($log->getObjectType() == 'PSE') {
                $productId = $log->getObjectRef();
                $extra['url'] = URL::getInstance()->absoluteUrl('admin/products/update?product_id='.$productId.'&current_tab=prices');
                $extra['content'] = 'PSE : '.$log->getObjectId();
            }
            $data[] = [
                $log->getCreatedAt()->format("d-m-Y H:i:s"),
                ($log->getShoppingfeedFeed()) ? $log->getShoppingfeedFeed()->getCountry()->getIsoalpha2()." - ".$log->getShoppingfeedFeed()->getLang()->getTitle() : '-',
                ($orderData) ? $orderData->getChannel() : '',
                $log->getLevelText(),
                [
                    "message" => $log->getMessage(),
                    "help" => $log->getHelp(),
                ],
                $extra
            ];
        }

        return new JsonResponse([
                "draw" => $request->get("draw"),
                "data" => $data,
                "recordsTotal"=> $queryCount->count(),
                "recordsFiltered"=> $querySearchCount->count()
            ]
        );
    }

    protected function filterByLevel(Request $request, ShoppingfeedLogQuery $logQuery)
    {
        if (array_key_exists('levels', $request->get('filter'))) {
            $logQuery->where(ShoppingfeedLogTableMap::COL_LEVEL . ' '. Criteria::IN . " (" . implode(',', $request->get('filter')['levels']) . ")");
        }
    }

    protected function applySearch(Request $request, ShoppingfeedLogQuery $logQuery)
    {
        if (array_key_exists('search', $request->get('filter'))) {
            $search = (string)$request->get('filter')['search'];

            if (strlen($search) > 2) {
                $logQuery->where(ShoppingfeedLogTableMap::COL_MESSAGE . ' LIKE ?', '%' . $search . '%', \PDO::PARAM_STR);
                $logQuery->_or()->where(ShoppingfeedLogTableMap::COL_HELP . ' LIKE ?', '%' . $search . '%', \PDO::PARAM_STR);
                $logQuery->_or()->where(ShoppingfeedOrderDataTableMap::COL_CHANNEL . ' LIKE ?', '%' . $search . '%', \PDO::PARAM_STR);
            }
        }
    }

    protected function getOrderColumnName(RequestStack $requestStack, LogService $logService)
    {
        $columnDefinition = $logService->defineColumnsDefinition()[
            (int) $requestStack->getCurrentRequest()->get('order')[0]['column']
        ];

        return $columnDefinition['orm'];
    }

    protected function applyOrder(RequestStack $requestStack, ShoppingfeedLogQuery $query, LogService $logService)
    {
        $query->orderBy(
            $this->getOrderColumnName($requestStack, $logService),
            $this->getOrderDir($requestStack)
        );
    }

    protected function getOrderDir(RequestStack $requestStack)
    {
        return (string) $requestStack->getCurrentRequest()->get('order')[0]['dir'] === 'asc' ? Criteria::ASC : Criteria::DESC;
    }
}