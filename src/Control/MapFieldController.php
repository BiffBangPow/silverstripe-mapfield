<?php

namespace BiffBangPow\Control;

use BiffBangPow\Helper\GeoLocationHelper;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;

class MapFieldController extends Controller
{
    private static $url_base;

    private static $allowed_actions = [
        'locate' => 'CMS_ACCESS_CMSMain'
    ];

    public function locate(HTTPRequest $request)
    {
        $search = trim($request->postVar('search'));
        $lat = 0;
        $lng = 0;

        if ($search === '') {
            return json_encode([
                'status' => -1,
                'message' => 'No search specified'
            ]);
        } else {
            $helper = GeoLocationHelper::create();
            $helper->setQueryData($search);
            $lat = $helper->getLatitude();
            $lng = $helper->getLongitude();
            if ($helper->getErrors() != '') {
                return json_encode([
                    'status' => -1,
                    'message' => $helper->getErrors()
                ]);
            }

            return json_encode([
                'status' => 1,
                'lat' => $lat,
                'lng' => $lng
            ]);
        }
    }

    public static function admin_url()
    {
        return self::get_admin_route() . '/';
    }

    public static function get_admin_route()
    {
        $rules = Director::config()->get('rules');
        $adminRoute = array_search(__CLASS__, $rules ?? []);
        return $adminRoute ?: static::config()->get('url_base');
    }
}
