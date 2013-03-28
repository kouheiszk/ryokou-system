<?php echo $this->Html->css('http://ajax.googleapis.com/ajax/libs/jqueryui/1/themes/ui-lightness/jquery-ui.css', null, array('inline' => false)); ?>
<?php echo $this->Html->script('http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js', array('inline' => false)); ?>
<?php echo $this->Html->script('http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js', array('inline' => false)); ?>
<?php echo $this->Html->scriptStart(array('inline' => false)); ?>
$(function() {
	var $inputCalcHotelId = $('#CalcHotelId');
	var $inputCalcHotelName = $('#CalcHotelName');
    $inputCalcHotelName.autocomplete({
    	minLength: 1,
        source: function( request, response ) {
            $.ajax({
                url: "<?php echo $this->Html->url('/hotels/autocomplete.json'); ?>",
                dataType: "json",
                data: {
                    term: request.term
                },
                success: function(data) {
                	response($.map(data, function(element) {
                        return {
                        	label: element.Hotel.name, 
                        	value: element.Hotel.id
                        }
                    }));
                }
            });
        },
        search: function(event, ui){
	        if (event.keyCode == 229) return false;
	        return true;
	    },
	    focus: function(event, ui) {
	    	$inputCalcHotelName.val(ui.item.label);
	    	$inputCalcHotelId.val(ui.item.value);
	    	return false;
	    },
        select: function(event, ui) {
        	$inputCalcHotelName.val(ui.item.label);
	    	$inputCalcHotelId.val(ui.item.value);
        	return false;
        }
    }).keyup(function(event) {
	    if (event.keyCode == 13) {
	        $(this).autocomplete("search");
	    }
	});
});
<?php echo $this->Html->scriptEnd(); ?>

<?php echo $this->Form->create('Calc', array('class' => 'form-horizontal')); ?>
	<fieldset>
		<?php // echo $this->Form->input('Calc.hotel_id', array('label' => 'ホテル', 'options' => $hotels, 'value' => $hotel_id)); ?>
		<?php echo $this->Form->input('Calc.hotel_name', array('label' => 'ホテル')); ?>
		<?php echo $this->Form->input('Calc.hotel_id', array('label' => 'ホテル', 'type' => 'hidden')); ?>
		<?php echo $this->Form->input('Calc.kyaku', array('label' => 'お客様人数')); ?>
		<?php echo $this->Form->input('Calc.compa', array('label' => 'コンパニオン人数')); ?>
		<?php echo $this->Form->submit('計算'); ?>
	</fieldset>
<?php echo $this->Form->end(); ?>

<?php foreach($result as $plan): ?>
	<a href="#plan-<?php echo h($plan['id']); ?>" class="color-<?php echo h($plan['type']); ?>"><?php echo h($plan['title']); ?></a><br>
<?php endforeach; ?>

<?php foreach($result as $plan): ?>
<section id="plan-<?php echo h($plan['id']); ?>">
	<div class="page-header">
		<h3 class="color-<?php echo h($plan['type']); ?>"><?php echo h($plan['title']); ?></h3>
	</div>
	
	<h4>料金テーブル</h4>
	
	<div class="row span12">
		<div class="span7">
			<!-- 料金テーブル -->
			<table class="table table-bordered">
				<thead>
					<tr>
						<th>お客様 : コンパニオン</th>
						<?php foreach($plan['price'] as $date=>$price): ?>
						<th><?php echo h($date); ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php for($i = 4; $i >= 0; $i--): ?>
					<tr>
						<?php if(isset($plan['price'][$date][$i])): ?>
							<th><?php echo h($i + 1); ?>名様 : 1人</th>
							<?php foreach($plan['price'] as $date=>$price): ?>
							<td><?php echo h(number_format($plan['price'][$date][$i])); ?>円</td>
							<?php endforeach; ?>
						<?php endif; ?>
					</tr>
					<?php endfor; ?>
				</tbody>
			</table>
		</div>
		<div class="span4">
			<!-- 延長料金 -->
			<table class="table table-bordered">
				<tbody>
					<?php if($plan['price_encho']): ?>
					<tr>
						<th>コンパニオン延長</th>
						<td><?php echo h(number_format($plan['price_encho'])); ?>円 / 1人30分</td>
					</tr>
					<?php endif; ?>
					<?php if($plan['price_tuika']): ?>
					<tr>
						<th>コンパニオン追加</th>
						<td><?php echo h(number_format($plan['price_tuika'])); ?>円 / 1人120分</td>
					</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
</section>

<section>
	<?php if(! isset($plan['total'][0]['sort']) || empty($plan['total'][0]['sort'])): ?>
		<h1 class="text-error">計算出来ません。</h1>
	<?php else: ?>
		<?php foreach($plan['total'] as $k=>$price_table): ?>
			<div class="page-header">
				<h4>料金パターン<?php echo h($k + 1); ?></h4>
			</div>
			<table class="table table-bordered">
				<thead>
					<tr>
						<th></th>
						<?php foreach($plan['price'] as $date=>$price): ?>
							<th colspan="3"><?php echo h($date); ?></th>
						<?php endforeach; ?>
					</tr>
					<tr>
						<th>料金項目</th>
						<?php foreach($plan['price'] as $date=>$price): ?>
						<th>単価</th><th>数量</th><th>金額</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php for($i = 0; $i < 5; $i++): if(! $price_table[$date]['pattern'][$i]) continue; ?>
						<tr>
							<th><?php echo h($i + 1); ?>名様 : コンパニオン1名 料金</th>
							<?php foreach($plan['price'] as $date=>$price): ?>
							<td><?php echo h(number_format($plan['price'][$date][$i])); ?>円</td>
							<td><?php echo h($price_table[$date]['pattern'][$i] * ($i + 1)); ?>名</td>
							<td><?php echo h(number_format($plan['price'][$date][$i] * $price_table[$date]['pattern'][$i] * ($i + 1))); ?>円</td>
							<?php endforeach; ?>
						</tr>
					<?php endfor; ?>
					<tr class="info">
						<th>合計金額（税込・サ込・入湯税込）</th>
						<?php foreach($plan['price'] as $date=>$price): ?>
						<td colspan="3"><b><?php echo h(number_format($price_table[$date]['price'])); ?>円</b></td>
						<?php endforeach; ?>
					</tr>
				</tbody>
			</table>
			
			<div class="clearfix">
				<p class="pull-right"><a href="#page-top"><i class="icon-circle-arrow-up"></i> ページの先頭へ</a></p>
			</div>
			<hr>
		<?php endforeach; ?>
	<?php endif; ?>
</section>
<?php endforeach; ?>

