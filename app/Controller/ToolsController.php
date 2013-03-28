<?php
App::uses('AppController', 'Controller');
/**
 * Tools Controller
 *
 * @property Tool $Tool
 */
class ToolsController extends AppController {
	public $uses = array('Hotel', 'Cplan', 'Gplan');
	
	public function index() {
		
	}

	public function calc($hotel_id = null)
	{
		$result = array();
		
		if($this->request->is('post'))
		{
			$hotel_id = $this->request->data['Calc']['hotel_id'];
			$kyaku = $this->request->data['Calc']['kyaku'];
			$compa = $this->request->data['Calc']['compa'];
			$this->Cplan->recursive = -1;
			$cplans = $this->Cplan->find('all', array(
				'conditions' => array(
					'hotel_id' => $hotel_id
				)
			));
			$prices = array();
			foreach($cplans as $k=>$cplan)
			{
				$prices[$k] = array();
				$prices[$k]['id'] = $cplan['Cplan']['id'];
				$prices[$k]['type'] = $cplan['Cplan']['type'];
				$prices[$k]['title'] = $cplan['Cplan']['title'];
				$prices[$k]['price_encho'] = $cplan['Cplan']['price_encho'];
				$prices[$k]['price_tuika'] = $cplan['Cplan']['price_tuika'];
				$prices[$k]['price'] = array();
				// 何対何の料金ブランがあるかを調査する
				for($i = 1; $i <= 3; $i++)
				{
					if(! empty($cplan['Cplan']['price_' . $i . '_name']))
					{
						$prices[$k]['price'][$cplan['Cplan']['price_' . $i . '_name']] = array();
						for($j = 0; $j < 5; $j++)
						{
							if((! empty($cplan['Cplan']['price_' . $i . '_' . ($j + 1) . '']))) 
							{
								$prices[$k]['price'][$cplan['Cplan']['price_' . $i . '_name']][$j]
									= $cplan['Cplan']['price_' . $i . '_' . ($j + 1) . ''];
							}
						}
					}
				}
			}

			$patterns = $this->get_patterns($kyaku, $compa);
			
			foreach($prices as $price)
			{
				$price['total'] = array();
				foreach($patterns as $k=>$pattern)
				{
					foreach($price['price'] as $date=>$price_table)
					{
						$pattern_price = $this->calc_pattern_price($pattern, $price_table);
						if(! $pattern_price) continue;
						$price['total'][$k][$date] = array(
							'pattern' => $pattern,
							'price' => $pattern_price
						);
					
						$price['total'][$k]['sort'] = $pattern_price;
					}
				}
				$price['total'] = Hash::sort($price['total'], '{n}.sort', 'asc');
				$result[] = $price;
			}
		}
		
		$this->set('hotels', $this->Hotel->find('list', array('fields' => array('id', 'name'))));
		$this->set('hotel_id', $hotel_id);
		$this->set('result', $result);
	}

	public function calc_stb($hotel_id = null)
	{
		$result = array();
		
		if($this->request->is('post'))
		{
			$hotel_id = $this->request->data['Calc']['hotel_id'];
			$kyaku = $this->request->data['Calc']['kyaku'];
			$compa = $this->request->data['Calc']['compa'];
			$this->Cplan->recursive = -1;
			$cplans = $this->Cplan->find('all', array(
				'conditions' => array(
					'hotel_id' => $hotel_id
				)
			));
			$prices = array();
			foreach($cplans as $k=>$cplan)
			{
				$prices[$k] = array();
				$prices[$k]['id'] = $cplan['Cplan']['id'];
				$prices[$k]['type'] = $cplan['Cplan']['type'];
				$prices[$k]['title'] = $cplan['Cplan']['title'];
				$prices[$k]['price_encho'] = $cplan['Cplan']['price_encho'];
				$prices[$k]['price_tuika'] = $cplan['Cplan']['price_tuika'];
				$prices[$k]['price'] = array();
				// 何対何の料金ブランがあるかを調査する
				for($i = 1; $i <= 3; $i++)
				{
					if(! empty($cplan['Cplan']['price_' . $i . '_name']))
					{
						$prices[$k]['price'][$cplan['Cplan']['price_' . $i . '_name']] = array();
						for($j = 0; $j < 5; $j++)
						{
							if((! empty($cplan['Cplan']['price_' . $i . '_' . ($j + 1) . '']))) 
							{
								$prices[$k]['price'][$cplan['Cplan']['price_' . $i . '_name']][$j]
									= $cplan['Cplan']['price_' . $i . '_' . ($j + 1) . ''];
							}
						}
					}
				}
			}

			$gcd = gmp_strval(gmp_gcd($kyaku, $compa));
			if($gcd == 1) {
				$patterns = $this->get_patterns($kyaku, $compa);
			} else {
				$patterns = $this->get_patterns($kyaku / $gcd, $compa / $gcd);
				foreach($patterns as $k=>$pattern) {
					foreach($pattern as $pattan_key=>$count) {
						$patterns[$k][$pattan_key] = $count * $gcd;
					}
				}
				if(empty($patterns)) {
					$patterns = $this->get_patterns($kyaku, $compa);
				}
			}
			
			foreach($prices as $price)
			{
				$price['total'] = array();
				foreach($patterns as $k=>$pattern)
				{
					foreach($price['price'] as $date=>$price_table)
					{
						$pattern_price = $this->calc_pattern_price($pattern, $price_table);
						if(! $pattern_price) continue;
						$price['total'][$k][$date] = array(
							'pattern' => $pattern,
							'price' => $pattern_price
						);
						
						$price['total'][$k]['sort'] = $pattern_price;
					}
				}
				$price['total'] = Hash::sort($price['total'], '{n}.sort', 'asc');
				$result[] = $price;
			}
		}
		
		$this->set('hotels', $this->Hotel->find('list', array('fields' => array('id', 'name'))));
		$this->set('hotel_id', $hotel_id);
		$this->set('result', $result);
		$this->render('calc');
	}

	function calc_pattern_price($pattern, $prices)
	{
		foreach($pattern as $k=>$count) {
			if($count && ! isset($prices[$k])) return false;
		}
		
		$sum = 0;
		foreach($pattern as $k=>$count)
		{
			if(! isset($prices[$k]) || ! $prices[$k]) continue;
			$sum += ($k + 1) * $count * $prices[$k];
		}

		return $sum;
	}

	function get_patterns($kyaku, $compa) {
		$result = array();
		
		for($i = 0; $i < pow($compa + 1, 5); $i++) {
			$num = sprintf("%05d", base_convert($i, 10, $compa + 1));
			$r = str_split($num);
			if(array_sum($r) != $compa) continue;
			$sum = 0;
			foreach($r as $k=>$count) {
				$sum += ($k + 1) * $count;
				if($sum > $kyaku) break;
			}
			if($sum != $kyaku) continue;
			$result[] = $r;
		}
		
		return $result;
	}
}
