<?php

App::uses('AppModel', 'Model');

/**
 * コンパニオンプラン、グループプランで共通の処理はこのクラスに記述されます
 * 
 * @name PlanModel
 */
class PlanModel extends AppModel 
{
	public $belongsTo = array(
		'Hotel' => array(
            'fields' => array(
	            'id', 
				'japan_id', 'japan', 'location_id', 'location', 
				'name', 'yomi', 'roman', 'address', 'geo',
				'score_sougou', 'score_yado', 'score_price', 'score_enkai', 'score_meal', 'score_lacation', 
				'sort_japan', 'sort_location', 
				'cplan_num', 'gplan_num', 
				'view_flag', 
			)
		)
	);

	public function find($type = 'first', $query = array()) 
	{
		switch($type)
		{
			case 'first':
			case 'all':
			case 'count':
				return parent::find($type, $query);
				break;
			default:
				$hotel_id = $this->Hotel->field('Hotel.id', array('Hotel.roman' => $type));
				$plans = $this->find('all', array(
					'conditions' => array(
						$this->alias.'.hotel_id' => $hotel_id
					)
				));
				
				if(! $hotel_id)
				{
					throw new BadRequestException('存在しない宿です。');
				}

				return $plans;
				break;
		}	
	}
	
	public function afterFind($results, $primary = false)
	{
		$results = parent::afterFind($results, $primary);

		if(Hash::check($results, '0.' . $this->alias . '.id'))
		{
			foreach($results as $key=>$value)
			{
				if($value)
					$results[$key] = $this->__findFormatter($value);
			}
		}

		return $results;
	}
	
	public function beforeSave($options = array())
	{
		// 最安値料金設定
		$min_price = $this->__getMinPrice($this->data);
		if($min_price)
		{
			$this->data[$this->alias]['min_price'] = $min_price;
		}

		return parent::beforeSave($options);
	}

	public function afterSave($created)
	{
		static $in = false;
		if($in) return parent::afterSave($created);
		$in = true;
		
		// プラン数の更新
		$this->__updatePlanNum($this->data[$this->alias]['hotel_id']);
		
		// ホテルのromanを取得
		if(isset($this->data['Hotel']['roman'])) $roman = $this->data['Hotel']['roman'];
		else $roman = $this->Hotel->field('roman', array('Hotel.id' => $this->data[$this->alias]['hotel_id']));
		if(! $roman) return false;
		
		// プラン画像格納パスを取得
		$plan_image_path = $this->path['path'];
		$plan_image_folder = $roman . '_p' . $this->id;
		
		// フォルダを生成し、そのインスタンスを格納
		App::uses('Folder', 'Utility');
		$folder = new Folder($plan_image_path . $plan_image_folder, true, 0777);
		
		// プラン画像をアップロードしてデータベースのカラムを更新する
		if($this->id)
		{
			if(isset($this->data[$this->alias]['plan_image']))
			{
				// 画像は現在3枚まで保存できる
				foreach($this->data[$this->alias]['plan_image'] as $k=>$image)
				{
					if(! $image['tmp_name']) continue;
	
					$tmp_path = $image['tmp_name'];
	
					$new_name = $plan_image_folder . DS . sprintf('%02d.jpg', $k);
					$new_path = $plan_image_path . $new_name;
	
					if(file_exists($tmp_path))
					{
						move_uploaded_file($tmp_path, $new_path);
						$this->data[$this->alias]['pic_' . $k] = $new_name;
					}
					
					// 昔のシステム様に、一応古い画像置き場にも画像を配置する
					// TODO 完全に新ステムに移行した場合は、この記述を削除
					//////////////////////////////////////////////////////////////////
					if(! isset($this->HotelImage))
					{
						App::uses('HotelImage', 'Model');
						$this->HotelImage = new HotelImage();
					}
					$hotel_image_path = $this->HotelImage->path['path'];
					if($this->alias == 'Cplan') $file_name_prefix = 'c';
					else $file_name_prefix = 'g';
					$hotel_name = $roman . DS . $file_name_prefix . '_plan_' . $this->id . '_pic_' . sprintf('%d.jpg', $k);
					$hotel_path = $hotel_image_path . $hotel_name;
					@copy($new_path, $hotel_path);
					//////////////////////////////////////////////////////////////////
				}
				
				unset($this->data[$this->alias]['plan_image']);
			}

			// プラン画像が設定されていなかった場合、画像を削除する
			for($i = 1; $i <= 3; $i++)
			{
				if(! isset($this->data[$this->alias]['pic_' . $i]) || empty($this->data[$this->alias]['pic_' . $i]))
				{
					$delete_name = $plan_image_folder . DS . sprintf('%02d.jpg', $i);
					$delete_path = $plan_image_path . $delete_name;
					@unlink($delete_path);
	
					// 昔のシステム様に、一応古い画像置き場の画像も削除する
					// TODO 完全に新ステムに移行した場合は、この記述を削除
					//////////////////////////////////////////////////////////////////
					if(! isset($this->HotelImage))
					{
						App::uses('HotelImage', 'Model');
						$this->HotelImage = new HotelImage();
					}
					$hotel_image_path = $this->HotelImage->path['path'];
					if($this->alias == 'Cplan') $file_name_prefix = 'c';
					else $file_name_prefix = 'g';
					$delete_hotel_name = $roman . DS . $file_name_prefix . '_plan_' . $this->id . '_pic_' . sprintf('%d.jpg', $i);
					$delete_hotel_path = $hotel_image_path . $delete_hotel_name;
					@unlink($delete_hotel_path);
					//////////////////////////////////////////////////////////////////
				}
			}
			$this->save($this->data);
		}

		// 最安値を更新
		$plan = $this->findById($this->id);
		$plans = $this->findAllByHotelIdAndType($plan[$this->alias]['hotel_id'], $plan[$this->alias]['type']);
		App::uses('Price', 'Model');
		$min_price = null;
		foreach($plans as $plan) 
		{
			if(! $min_price || $min_price > $plan[$this->alias]['min_price'])
			{
				$min_price = $plan[$this->alias]['min_price'];
			}
		}
		$type_id = $this->Hotel->Price->type_ids[$plan[$this->alias]['type']];
		$price = $this->Hotel->Price->findByHotelIdAndTypeId($plan[$this->alias]['hotel_id'], $type_id);
		$price[$this->Hotel->Price->alias]['hotel_id'] = $plan[$this->alias]['hotel_id'];
		$price[$this->Hotel->Price->alias]['type_id'] = $type_id;
		$price[$this->Hotel->Price->alias]['min_price'] = $min_price;
		$this->Hotel->Price->save($price);

		parent::afterSave($created);
		$in = false;
	}
	
	public function beforeDelete($cascade = true) 
	{
		// プラン数更新のため
		$this->data = $this->read(null, $this->id);
		
		// TODO プランの画像を削除
		
		return parent::beforeDelete($cascade);
	}
	
	public function afterDelete()
	{
		// プラン数の更新
		$this->__updatePlanNum($this->data[$this->alias]['hotel_id']);
		
		// ホテルのromanを取得
		if(isset($this->data['Hotel']['roman'])) $roman = $this->data['Hotel']['roman'];
		else $roman = $this->Hotel->field('roman', array('Hotel.id' => $this->data[$this->alias]['hotel_id']));
		if(! $roman) return false;
		
		// 画像の削除
		$plan_image_path = $this->path['path'];
		$plan_image_folder = $roman . '_p' . $this->id;
		
		// フォルダを生成し、そのインスタンスを格納
		App::uses('Folder', 'Utility');
		$folder = new Folder($plan_image_path . $plan_image_folder);
		$folder->delete();

		// 昔のシステム用に、一応古い画像置き場の画像も削除する
		// TODO 完全に新ステムに移行した場合は、この記述を削除
		//////////////////////////////////////////////////////////////////
		if(! isset($this->HotelImage))
		{
			App::uses('HotelImage', 'Model');
			$this->HotelImage = new HotelImage();
		}
		$hotel_image_path = $this->HotelImage->path['path'];
		for($i = 1; $i <= 3; $i++)
		{
			if($this->data[$this->alias]['pic_' . $i])
			{
				if($this->alias == 'Cplan') $file_name_prefix = 'c';
				else $file_name_prefix = 'g';
				$delete_hotel_name = $roman . DS . $file_name_prefix . '_plan_' . $this->id . '_pic_' . sprintf('%d.jpg', $i);
				$delete_hotel_path = $hotel_image_path . $delete_hotel_name;
				unlink($delete_hotel_path);
			}
		}
		//////////////////////////////////////////////////////////////////
		
		return parent::afterDelete();
	}
	
	/**
	 * 最安値料金を取得します
	 * 
	 * @name __getMinPrice
	 * @param $data {Array} 保存しようとしているデータ
	 */
	private function __getMinPrice($plan)
	{
		// 最安値料金設定
		$min_price = 0;
			
		if(isset($plan['Gplan']))
		{
			for($i = 1; $i <= 3; $i++)
			{
				if(! isset($plan['Gplan']['price_'.$i])) continue;
				$price = $plan['Gplan']['price_'.$i];
				if($price && (! $min_price || $price < $min_price))
				{
					$min_price = $price;
				}
			}
		}
		elseif(isset($plan['Cplan']))
		{
			for($i = 1; $i <= 3; $i++)
			{
				for($j = 1; $j <=5; $j++)
				{
					if(! isset($plan['Cplan']['price_'.$i.'_'.$j])) continue;
					$price = $plan['Cplan']['price_'.$i.'_'.$j];
					if($price && (! $min_price || $price < $min_price))
					{
						$min_price = $price;
					}
				}
			}
		}
		
		return $min_price;
	}
	
	/**
	 * afterFindから呼ばれるフォーマッタ
	 * 
	 * @name __findFormatter
	 */
	private function __findFormatter($plan)
	{	
		if(isset($plan['Hotel']['roman'])) $roman = $plan['Hotel']['roman'];
		else $roman = $this->Hotel->field('Hotel.roman', array('Hotel.id' => $plan[$this->alias]['hotel_id']));

		if($roman)
		{
			$is_set_plan_image = false;
			for($i = 1; $i <= 3; $i++)
			{
				if(! isset($plan[$this->alias]['pic_' . $i])) continue;
				$image = $plan[$this->alias]['pic_' . $i];
				$plan[$this->alias]['image_url_' . $i] = ($image) ? $this->path['url'] . $image : false;
				if($image) $is_set_plan_image = true;
			}
			
			if(! $is_set_plan_image && isset($plan[$this->alias]['type']))
			{
				$plan[$this->alias]['image_url_1'] = $this->path['url'] . 'default' . DS . $plan[$this->alias]['type'] . '.jpg';
			}
		}
	
		return $plan;
	}

	/**
	 * Hotelのプラン数を更新する処理
	 * 
	 * @name __updatePlanNum
	 * @param $hotel_id {Integer} ホテルID
	 */
	private function __updatePlanNum($hotel_id)
	{
		if(! isset($this->Hotel))
		{
			App::uses('Hotel', 'Model');
			$this->Hotel = new Hotel();
		}
		$this->Hotel->recursive = -1;
		$hotel = $this->Hotel->find('first', array('conditions' => array('Hotel.id' => $hotel_id)));
		if($hotel)
		{
			$hotel['Hotel'][strtolower($this->alias) . '_num'] = $this->find('count', array(
				'conditions' => array(
					$this->alias.'.hotel_id' => $hotel_id,
					$this->alias.'.view_flag' => 'T'
				)
			));

			return $this->Hotel->save($hotel);
		}
		
		return false;
	}
}
