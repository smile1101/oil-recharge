<?php
/**
 * 充值辅助函数
 */

/**
 * xml转换数组
 * @param null $data
 * @return null|string
 */
function fromXml($data = null)
{
    if (empty($data))
        $data = file_get_contents("php://input");

    if (empty($data))
        return null;

    libxml_disable_entity_loader(true);

    return json_decode(json_encode(simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
}

/**
 * 日志记录
 * @param $data
 * @param $dir
 * @param null $orders
 */
function zlw_logger($data, $dir, $orders = null)
{
    if (!file_exists($dir))
        @mkdir($dir);

    $file = $dir . '/' . date('ymd') . '.log';

    $data = '[ ' . date('Y-m-d H:i:s') . ' ] ===> ' . (!empty($orders) ? 'api::' . $orders : '') . ' response::' . var_export($data, true);

    @file_put_contents($file, $data . "\n", FILE_APPEND);
}