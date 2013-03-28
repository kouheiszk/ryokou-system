<?php

App::uses('AppModel', 'Model');

class Hotel extends AppModel
{
	public $useDbConfig = 'yado';
	public $name = 'Hotel';
	public $useTable = 'hotel';
	public $order = array('Hotel.id' => 'ASC');
	
	public $hasMany = array(
		'Cplan' => array(
			'order' => array(
				'Cplan.type' => 'DESC'
			),
			'dependent' => true
		),
		'Gplan' => array(
			'order' => array(
				'Gplan.type' => 'DESC'
			),
			'dependent' => true
		),
		'Kuchikomi' => array(
			'conditions' => array(
				'Kuchikomi.view_flag' => 'T'
			),
			'order' => array(
				'Kuchikomi.created_at' => 'DESC'
			),
			'dependent' => true
		),
		'HotelImage' => array(
			'order' => array(
				'HotelImage.is_top' => 'DESC',
				'HotelImage.position' => 'ASC'
			),
			'fields' => array(
				'id',
	            'type',
	            'is_top', 
	            'name',
	            'position',
	            'description'
			),
			'dependent' => true
		),
		'Price' => array(
			'dependent' => true
		),
		'HotelKankou'
	);
	
	public $hasOne = array(
		'Ciine' => array(
			'className' => 'Iine',
			'conditions' => array(
				'Ciine.type' => 'companion'
			),
			'dependent' => true
		),
		'Giine' => array(
			'className' => 'Iine',
			'conditions' => array(
				'Giine.type' => 'companion'
			),
			'dependent' => true
		),
		'TopImage' => array(
			'className' => 'HotelImage',
			'conditions' => array(
				'TopImage.is_top' => true
			),
			'dependent' => true
		)
	);
	
	public $belongsTo = array(
		'Japan',
		'Location'
	);
	
	public $validate = array(
        'japan_id' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => '都道府県を選択してください。'
	        )
        ),
        'location_id' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => '温泉名を選択してください。'
	        )
        ),
        'name' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => '宿名を入力してください。',
	        )
        ),
        'yomi' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => '宿名（よみ）を入力してください。',
	        )
        ),
        'roman' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => '宿名（アルファベット）を入力してください。',
	        ),
	        'alphanumeric' => array(
		        'rule' => '/^[a-z_]+$/i',
	            'message' => 'アルファベットで入力してください。',
		    ),
		    'isUnique' => array(
		        'rule' => 'isUnique',
	            'message' => 'すでに使われている識別用アルファベットです。',
			)
        ),
        'catch' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'キャッチコピーを入力してください。',
	        )
        ),
        'profile' => array(
        	'notEmpty' => array(
	            'rule'    => 'notEmpty',
	            'message' => 'プロフィールを入力してください。',
	        )
        )
    );
	
	public function find($type = 'first', $query = array()) 
	{
		switch($type)
		{
			case 'first':
			case 'all':
			case 'list':
			case 'count':
			case 'distance':
				return parent::find($type, $query);

			default:
				$hotel = $this->find('first', array(
					'conditions' => array(
						'Hotel.roman' => $type
					)
				));
				
				if(! $hotel)
				{
					throw new BadRequestException('存在しない宿名です。');
				}

				return $hotel;
		}
		
	}
	
	public function getSameAreaHotel($type = 'Both', $id = null, $options = array())
	{
		if(! $id) $id = $this->id;
		$result = array();
		if($id)
		{
			$current = $this->read(null, $id);
			$this->recursive = 0;
			if($type == 'c' || $type == 'g')
			{
				$result = $this->find('all', array_merge(array(
					'conditions' => array(
						'Hotel.'.$type.'plan_num >' => 0,
						'Japan.small_area' => $current['Japan']['small_area']
					)
				),$options));	
			}
			else 
			{
				$result = $this->find('all', array_merge(array(
					'conditions' => array(
						'Japan.small_area' => $current['Japan']['small_area']
					)
				),$options));
			}
			
		}
		return $result;
	}
	
	public function getTopImage($id)
	{
		$hotel = $this->find('first', array('conditions' => array($this->alias.'.id' => $id)));
		$top = $hotel['TopImage'];
		unset($hotel);
		return $top;
	}
	
	public function getMinPrice($id, $type = 'c')
	{
		$min = false;
		
		$hotel = $this->read(null, $id);
		if($type == 'c')
		{
			$plans = $hotel['Cplan'];
		}
		else if($type == 'g')
		{
			$plans = $hotel['Gplan'];
		}
		else 
		{
			$plans = $hotel['Cplan'] + $hotel['Gplan'];
		}
		
		unset($hotel);
		
		foreach($plans as $plan)
		{
			if($plan['view_flag'] != 'T' 
			|| ! isset($plan['min_price'])
			|| empty($plan['min_price'])) break;
			
			if(! $min || $min > $plan['min_price'])
			{
				$min = $plan['min_price'];
			}
		}
		
		return $min;
	}
	
	public function getConditionFromKeyword($keyword)
	{
		// キーワードを取得
		$keywords = explode(' ', $keyword);
		$keyword_count = count($keywords);
		
		$conditions = array();
		
		// 特典は使わないけど、ユーザ側の画面では結果に出すから、一応残しておきます
		// $toku_conditions = array();
		
		// クエリ作成
		foreach($keywords as $keyword)
		{
			if($keyword_count == 1 && $keyword == '都道府県・温泉地・宿名などを入力してください')
			{
			}
			elseif($keyword)
			{
				// コンパプラン
				$this->Cplan->recursive = 0;
				$plans = $this->Cplan->find('all', array(
					'conditions' => array(
						'OR' => array(
							array('Cplan.title LIKE ?' => '%'.$keyword.'%'),
							array('Cplan.toku LIKE ?' => '%'.$keyword.'%'),
							array('Cplan.profile LIKE ?' => '%'.$keyword.'%'),
							array('Cplan.content LIKE ?' => '%'.$keyword.'%')
						)
					),
					'fields' => array(
						'Cplan.id', 'Cplan.hotel_id' 
					)
				));
				$plans = Set::combine($plans, '{n}.Cplan.id', '{n}.Cplan.hotel_id');
				$plans = array_unique($plans);
				$c_plan_conditions = array();
				if(count($plans) > 0)
				{
					$c_plan_conditions[] = array(
						'Hotel.id' => $plans
					);
				}
				unset($plans);
				
				// グループプラン
				$this->Gplan->recursive = 0;
				$plans = $this->Gplan->find('all', array(
					'conditions' => array(
						'OR' => array(
							array('Gplan.title LIKE ?' => '%'.$keyword.'%'),
							array('Gplan.catch LIKE ?' => '%'.$keyword.'%'),
							array('Gplan.profile LIKE ?' => '%'.$keyword.'%'),
							array('Gplan.content LIKE ?' => '%'.$keyword.'%')
						)
					),
					'fields' => array(
						'Gplan.id', 'Gplan.hotel_id' 
					)
				));
				$plans = Set::combine($plans, '{n}.Gplan.id', '{n}.Gplan.hotel_id');
				$plans = array_unique($plans);
				$g_plan_conditions = array();
				if(count($plans) > 0)
				{
					$g_plan_conditions[] = array(
						'Hotel.id' => $plans
					);
				}
				unset($plans);
				
				// ホテルテーブルから
				$conditions[]['OR'] = array_merge(array(
					array('Hotel.japan LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.location LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.name LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.yomi LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.roman LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.profile LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.toku LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.address LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.access LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.bath LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.room LIKE ?' => '%'.$keyword.'%'),
					array('Hotel.public LIKE ?' => '%'.$keyword.'%'),
					array('Japan.wide_area LIKE ?' => '%'.$keyword.'%'),
					array('Japan.small_area LIKE ?' => '%'.$keyword.'%')
				), $c_plan_conditions, $g_plan_conditions);
				
				// $toku_conditions[]['OR'] = array(
				// 	array('Tokusyuu.keyword LIKE ?' => '%'.$keyword.'%')
				// );
			}
		}

		return $conditions;
	}
	
	public function beforeSave($options = array())
	{
		// iconを整理する
		for($i = 1; $i <= 9; $i++)
		{
			$key = sprintf("icon_%02d", $i);
			if(isset($this->data['Hotel'][$key]) && is_array($this->data['Hotel'][$key]))
			{
				$this->data['Hotel'][$key] = 
					isset($this->data['Hotel'][$key][0]) ? $this->data['Hotel'][$key][0] : '';
			}
		}
		
		// 都道府県IDから都道府県を設定
		if(isset($this->data['Hotel']['japan_id']))
		{
			$before_recursive = $this->Japan->recursive;
			$this->Japan->recursive = 0;
			$japan = $this->Japan->find('first', array('conditions' => array('Japan.id' => $this->data['Hotel']['japan_id'])));
			$this->Japan->recursive = $before_recursive;
			if($japan)
			{
				$this->data['Hotel']['japan'] = $japan['Japan']['prefecture'];
			}
		}

		// 温泉地IDから温泉地を設定
		if(isset($this->data['Hotel']['location_id']))
		{
			$before_recursive = $this->Location->recursive;
			$this->Location->recursive = 0;
			$location = $this->Location->find('first', array('conditions' => array('Location.id' => $this->data['Hotel']['location_id'])));
			$this->Location->recursive = $before_recursive;
			if($location)
			{
				$this->data['Hotel']['location'] = $location['Location']['name'];
			}
		}

		// ソート用の都道府県IDを設定
		if((isset($this->data[$this->alias]['sort_japan']) || array_key_exists('sort_japan', $this->data[$this->alias])) && empty($this->data[$this->alias]['sort_japan'])) {
			$this->recursive = -1;
			$max_sort = $this->field('sort_japan', array(
				$this->alias . '.japan_id' => $this->data[$this->alias]['japan_id'],
				$this->alias . '.sort_japan !=' => null 
			), array(
				$this->alias . '.sort_japan' => 'DESC'
			));
			$max_sort = ($max_sort) ? $max_sort : $this->data[$this->alias]['japan_id'] * 1000;
			$this->data[$this->alias]['sort_japan'] = ++$max_sort;
		}

		// ソート用の温泉地IDを設定
		if((isset($this->data[$this->alias]['sort_location']) || array_key_exists('sort_location', $this->data[$this->alias])) && empty($this->data[$this->alias]['sort_location'])) {
			$this->recursive = -1;
			$max_sort = $this->field('sort_location', array(
				$this->alias . '.location_id' => $this->data[$this->alias]['location_id'],
				$this->alias . '.sort_location !=' => null 
			), array(
				$this->alias . '.sort_location' => 'DESC'
			));
			$max_sort = ($max_sort) ? $max_sort : $this->data[$this->alias]['location_id'] * 1000;
			$this->data[$this->alias]['sort_location'] = ++$max_sort;
		}

		return parent::beforeSave($options);
	}

	public function afterSave($created) 
	{
		parent::afterSave($created);
		
		// 新規作成時に、宿の画像用ディレクトリを作成する
		if($created) 
		{
			// プラン画像格納パスを取得
			$yado_path = realpath(ROOT . DS . '..' . DS . 'yado') . DS . $this->data[$this->alias]['roman'];
			
			// フォルダを生成し、そのインスタンスを格納
			App::uses('Folder', 'Utility');
			new Folder($yado_path, true, 0777);
			
			
		}
	}
	
	public function beforeDelete($cascade = true) 
	{
		$this->tmpdata = $this->find('first', array('conditions' => array($this->alias.'.id' => $this->id)));
		return parent::beforeDelete($cascade);
	}
	
	public function afterDelete() 
	{
		parent::afterDelete();
		
		$yado_path = $this->HotelImage->path['path'] . $this->tmpdata[$this->alias]['roman'];
		if(file_exists($yado_path))
		{
			// フォルダを生成し、そのインスタンスを格納
			App::uses('Folder', 'Utility');
			$folder = new Folder($yado_path, true);
			$folder->delete();
		}
	}
	
	protected function _findDistance($state, $query, $results = array())
	{
		return $this->findDistance($state, $query, $results);
	}
}
