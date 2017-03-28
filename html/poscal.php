<?php
	$difficulty=$emercoin->getdifficulty();
	$difficulty=$difficulty['proof-of-stake'];
?>

<div class="container">

	  <div class="row">
	    <div class="col-sm-offset-2 col-sm-8">
	      Coins <input type="input" class="form-control" id="inputCoins" placeholder="Coins">
	    </div>
	  </div>
	  <div class="row">
	    <div class="col-sm-offset-2 col-sm-8">
	      Days <input type="input" class="form-control" id="inputAge" placeholder="Age (days) [31-90]" value="31">
	    </div>
	  </div>
	  <div class="row">
	    <div class="col-sm-offset-2 col-sm-8">
	      PoS Difficulty <input type="input" class="form-control" id="inputDiff" placeholder="Difficulty" value="<?php echo $difficulty; ?>">
	    </div>
	  </div>
	  <br>
	  <div class="row">
	    <div class="col-sm-offset-2 col-sm-2">
	      <button class="btn btn-default" id="calcBtn" >Calculate</button>
	    </div>
		<div class="col-sm-6">
	    	<table class="table">
					<tr><th><?php echo lang("MINTING_CHANCE");?><small><sub><?php echo lang("WITHIN_H");?></sub></small> [%]</th><th><?php echo lang("ESTIMATED_REWARD");?> [EMC]</th><tr>
					<tr><td id="mintChanceTD">-</td><td id="rewardTD">-</td></tr>
				</table>
	    </div>
	  </div>

</div>

<script>

$('#calcBtn').on('click', function() {
	calculateProbBlockToday($('#inputAge').val(),$('#inputCoins').val(),$('#inputDiff').val());
});

$('#inputCoins').on('keyup', function(e) {
	if (e.which == 13) {
		calculateProbBlockToday($('#inputAge').val(),$('#inputCoins').val(),$('#inputDiff').val());
	}
});
$('#inputAge').on('keyup', function(e) {
	if (e.which == 13) {
		calculateProbBlockToday($('#inputAge').val(),$('#inputCoins').val(),$('#inputDiff').val());
	}
});
$('#inputDiff').on('keyup', function(e) {
	if (e.which == 13) {
		calculateProbBlockToday($('#inputAge').val(),$('#inputCoins').val(),$('#inputDiff').val());
	}
});

function getProb(days, coins, difficulty) {
	var prob=0;
	if (days > 30) {
			var maxTarget = Math.pow(2, 224);
			var target = maxTarget/difficulty;
			var dayWeight = Math.min(days, 90)-30;
			prob = (target*coins*dayWeight)/Math.pow(2, 256);
	}
	return prob;
};
function calculateProbBlockToday(days, coins, difficulty) {
	var prob = getProb(days, coins, difficulty);
    var res = 1-(Math.pow((1-prob),600));
	res = res*144;
	res = res*100;
	
	var reward=0;
		if (days>30) {
			reward=((days*coins)/365)*0.06;
		}	
	$('#mintChanceTD').html(Math.round(res * 1000000) / 1000000);
	$('#rewardTD').html(Math.round(reward * 1000000) / 1000000);
};
</script>
