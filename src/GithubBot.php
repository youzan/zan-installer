<?php
/**
 * Created by IntelliJ IDEA.
 * User: nuomi
 * Date: 16/6/17
 * Time: 下午12:19
 */

namespace Zan\Installer\Console;


class GithubBot
{
    const ACCESS_TOKEN = '583b23707e61512ce30656237e597391e5a21f1d';
    /**
     * @var static
     */
    private static $_instance = null;

    /**
     * @return static
     */
    final public static function instance()
    {
        return static::singleton();
    }

    final public static function singleton()
    {
        if (null === static::$_instance) {
            static::$_instance = new static();
        }
        return static::$_instance;
    }

    /**
     * @return static
     */
    final public static function getInstance()
    {
        return static::singleton();
    }

    public static function download($api = '', $format = 'original', $path = null)
    {
        $url = self::getDownloadLink($api);
        if (!$url) {
            throw new RuntimeException('ERROR: Download fail: (');
        }
        return self::_download($url, $format, $path);
    }

    private static function _download($url, $format = 'original', $path = null)
    {
        $url = self::addTokenToURL($url);

        $context = stream_context_create([
            'http' => [
                'header' => 'User-Agent: agalwood',
            ]
        ]);
        $res = file_get_contents($url, false, $context);
        $result = self::format($res, $format);

        if ($path) {
            $result = file_put_contents($path, $res);
        }

        return $result;
    }

    private static function getDownloadLink($api)
    {
        $data = self::_download($api, 'json');
        if (!isset($data['zipball_url'])) {
            throw new RuntimeException('ERROR: Get download link fail: (');
            exit();
        }
        return $data['zipball_url'];
    }

    private static function addTokenToURL($url = '')
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if ($query) {
            $result = $url . '&access_token=' . self::ACCESS_TOKEN;
        } else {
            $result = $url . '?access_token=' . self::ACCESS_TOKEN;
        }
        return $result;
    }

    private static function format($data, $format = 'original')
    {
        $result = $data;
        switch ($format) {
            case 'original':
                break;
            case 'json':
                $result = json_decode($result, true);
                break;
            default:
                break;
        }
        return $result;
    }
}
