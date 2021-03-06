<?php

namespace Arrow768\Sdv2IpStoreag;

/**
 * SDv2 Items Provider for Store
 * 
 * A Items Provider for Alongubkins Store Plugin
 * Contains the required functions that are used by SDv2
 * 
 * This file is Part of SDv2IP-Store
 * SDv2IP-Store is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version. 
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * 
 * @package    SDv2IP-Store
 * @author     Werner Maisl
 * @copyright  (c) 2013-2014 - Werner Maisl
 * @license    GNU AGPLv3 http://www.gnu.org/licenses/agpl-3.0.txt
 */
class store_credits
{

    function add_item($sd_user, $sd_user_infos, $sd_item_handler_params)
    {
        \Log::info("Store Items Provider - Store Credits called");
        \Log::info("User Mail:" . $sd_user->email);
        \Log::info("Credits:" . $sd_item_handler_params->credits);

        $steamid64 = "";
        foreach ($sd_user_infos as $sd_user_info)
        {
            if ($sd_user_info->type == "steamid")
            {
                $steamid64 = $sd_user_info->value;
            }

            \Log::info("User Info Type:" . $sd_user_info->type);
            \Log::info("User Info Value:" . $sd_user_info->value);
        }
        \Log::info("Steamid64:" . $steamid64);
        
        $steamid = $this->community_to_steamid($steamid64);
        \Log::info("Steamid:". $steamid);
        
        $store_auth = $this->steamid_to_auth($steamid);
        \Log::info("Auth:". $store_auth);
        
        $store_user = \DB::Connection('ag_store')->table('store_users')->where('auth',$store_auth)->first();
        \Log::info('Store User Name: '.$store_user->name);
        \Log::info('Store User Credits: '.$store_user->credits);
        $new_credits = $store_user->credits + $sd_item_handler_params->credits;
        
        \DB::Connection('ag_store')->table('store_users')->where('id',$store_user->id)->update(array('credits'=>$new_credits));
        \Log::info('Updated Store Credits to: '.$new_credits);
        return true;
    }

    function remove_item($sd_user, $sd_user_infos, $sd_user_params)
    {
        
    }

    private function steamid_to_auth($steamid)
    {
        //from https://forums.alliedmods.net/showpost.php?p=1890083&postcount=234
        $toks = explode(":", $steamid);
        $odd = (int) $toks[1];
        $halfAID = (int) $toks[2];

        return ($halfAID * 2) + $odd;
    }

    private function auth_to_steamid($authid)
    {
        $steam = array();
        $steam[0] = "STEAM_0";

        if ($authid % 2 == 0)
        {
            $steam[1] = 0;
        }
        else
        {
            $steam[1] = 1;
            $authid -= 1;
        }
        $steam[2] = $authid / 2;
        return $steam[0] . ":" . $steam[1] . ":" . $steam[2];
    }

    private function steamid_to_community($steamid)
    {
        $parts = explode(':', str_replace('STEAM_', '', $steamid));

        $result = bcadd(bcadd('76561197960265728', $parts['1']), bcmul($parts['2'], '2'));
        $remove = strpos($result, ".");
        if ($remove != false)
        {
            $result = substr($result, 0, strpos($result, "."));
        }
        return $result;
    }

    private function community_to_steamid($community)
    {
        if (substr($community, -1) % 2 == 0)
            $server = 0;
        else
            $server = 1;
        $auth = bcsub($community, '76561197960265728');
        if (bccomp($auth, '0') != 1)
        {
            return false;
        }
        $auth = bcsub($auth, $server);
        $auth = bcdiv($auth, 2);
        return 'STEAM_0:' . $server . ':' . $auth;
    }

}
