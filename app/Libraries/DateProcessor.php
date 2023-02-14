<?php namespace App\Libraries;

class DateProcessor
{
    public function getDates($filter, $min_date, $max_date, $step_quantity = 10){
        $date_to = $max_date;
        $date_from = date('Y-m-d H:i:s', strtotime($min_date));
        if(!empty($filter['date_from'])){
            if($filter['date_from'] == 'week'){
                $date_from = date('Y-m-d H:i:s', strtotime('-1 week'));
            } else
            if($filter['date_from'] == 'month'){
                $date_from = date('Y-m-d H:i:s', strtotime('-1 month'));
            } else {
                $date_from = date('Y-m-d H:i:s', strtotime('-6 month'));
            }
        }
        
        $date_from_numeric = strtotime($date_from);
        $date_to_numeric = strtotime($date_to);
        $date_difference = $date_to_numeric - $date_from_numeric;
        $date_diff_chunk = ceil($date_difference/$step_quantity-1);
        $date_format = "d M";
        if($date_diff_chunk / (60 * 60 * 24) < 1){
            $date_format = "d M H:i";
        }
        $result = [];
        $date_from_numeric -= $date_diff_chunk;
        $date_count = $date_from_numeric;
        for($i = 0; $i < $step_quantity+1; $i++){
            $start_date = date('Y-m-d H:i:s', $date_count);
            $date_count += $date_diff_chunk;
            $end_date = date('Y-m-d H:i:s', $date_count);
            if($i == $step_quantity){
                $end_date = $max_date;
            }
            $result['start_dates'][] = $start_date;
            $result['end_dates'][] = $end_date;
            $label = date($date_format, strtotime($start_date)).' - '.date($date_format, strtotime($end_date));
            $label_translated = $label;
            $result['labels'][] = $label_translated;
        }
        return $result;
    }
    private function translateMonths($label){
        $lang = JFactory::getLanguage();
        $language_tag = $lang->getTag();
        $language = parse_ini_file('./language/'.$language_tag.'/'.$language_tag.'.mod_ilimhane.ini');
        $month_config = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        foreach($month_config as $month_name){
            $label = str_replace($month_name, $language['MOD_ILIMHANE_DATETIME_MONTH_'.strtoupper($month_name)], $label);
        }
        return $label;
    } 
} 