jQuery(document).ready(function ($) {

    var countdown;
    clearInterval(countdown);
    countdown = 0;
    var checker;
    clearInterval(checker);
    checker = 0;

    var timeleft = 900;
	var iVal = 5000;
    var expired_countdown_content = 'The time limit has been reached. Please close this window and submit your order to try again.';

    function timer() {
        timeleft = timeleft -1;
        if(timeleft <= 0)
        {
            clearInterval(countdown);
            countdown = 0;
            clearInterval(checker);
            checker = 0;
            document.getElementById('cc-border').innerHTML = expired_countdown_content;

        }
        var minutes = Math.floor(timeleft/60);
        var seconds = timeleft%60;
        var seconds_string = "0" + seconds;
        seconds_string = seconds_string.substr(seconds_string.length - 2);
		if(document.getElementById("timer") != null){
			document.getElementById("timer").innerHTML = minutes + ":" + seconds_string;
		}else{
			timeleft = 0;
		}
  }

  function submit_wp_cc_form() {


    if(typeof timeleft == 'undefined') {
      timeleft = (Drupal.settings.wp_cc.time_limit);
      countdown = setInterval(timer, 1000); //start timer
    } else {
        $('form#wpsc_checkout_forms').submit();
    }
  }

$("#cc-hidden-purchase-btn").on('click', function (e) {
		$.ajax({ 
			type: 'POST',
			url: ajaxurl,
			timeout: 5000,
			dataType: 'text',
			data :  {
				action : 'cc_coin',
                total_price : total_price,
			},
			error: function() {
				document.getElementById("cboxLoadedContent").innerHTML += '<div class="warning">Could not contact CheckoutCrypto API server.</div>';
			},
			success: function(received) {
				var arr = $.parseJSON(received);
				if(timeleft > 0) {
					html = '<div id="cc-wrapper">';
					html += '<div id="cc-border">';
					html += '<div id="cc_pay"></div>';
					html += '<div id="oc-cc-payment-info">Please select your preferred cryptocurrency to continue with payment</div>';
					html += '<div id="cc_coin_select_wrapper" class="oc_cc_show">';
					for (var i in arr) 
					{
						html += "<div class='cc_coin_wrapper' id='cc_coin_wrapper_" + arr[i]['coin_code'].toLowerCase() + "'>";
                        html += "<div class='cc_coin' id='cc_coin_" + arr[i]['coin_code'].toLowerCase() + "' style='background: url("+arr[i]['coin_img'].toLowerCase()+" ); height: 150px; width: 150px; left no-repeat;'></div><div id='cc_coin_"+ arr[i]['coin_code'].toLowerCase() + "' class='coin_amount'> " + arr[i]['coin_code'].toUpperCase() + ": " +arr[i]['coin_total'] + "</div></div>";
					}
					html +=  '</div>';
					html += '<div id="cc_payment_processing_wrapper" class="oc_cc_hide"><input name="oc_cc_selected_coin" value="" type="hidden">';
					html += '<div id="oc_cc_payment_address_container">';
					html += '<div class="form-item form-type-textfield form-item-oc-cc-payment-address">';
					html += '<input readonly="readonly" size="50" id="oc-cc-payment-address" name="oc_cc_payment_address" value="" maxlength="128" class="form-text" type="text">';
					html += '</div></div>';
					html += '<div id="oc_cc_payment_qr_address_container"></div>';
					html += '<div class="center"><?php echo $text_pre_timer ?><span id="timer" style="font-weight: bold;"></span><?php echo $text_post_timer ?></div>';
					html += '<div id="cc_progress_status">This window will auto-refresh status until order is complete</div></div></div></div>';
			        } else {
					html  = expired_countdown_content;
				} 


				$.colorbox({ overlayClose: true,
					opacity: 0.5,
					width: '650px',
					height: '450px',
					href: false,
					html: html,
					onComplete: function() {
						 $("body").click(function(e) {
								if($(e.target).parent().is('.cc_coin_wrapper')) {
								    console.log(e.target.id);
								    var coin_code = e.target.id;
								    coin_code = coin_code.substring(8).toUpperCase();
  									if(coin_code == "BTC"){
										iVal = iVal *2;
										timeleft = timeleft *2;
                        			}
								    checkoutcrypto_order_details(coin_code);
								}
						});
							function checkoutcrypto_check (coin_code) {
								if(timeleft > 0) {
									$.ajax({ 
										type: 'POST',
										url: ajaxurl,
										timeout: 5000,
										dataType: 'text',
	       							                data: {
										    action : 'cc_checkReceived',
                                            coin_code: coin_code,
								                },
										error: function() {
											document.getElementById("cboxLoadedContent").innerHTML += '<div class="warning">Something went wrong. Please close this window and resubmit the order to try again.</div>';
										},
										success: function(received) {
                                            console.log(received);

                                            var answer = $.parseJSON(received);
                                            if(answer["status"] == 'success' || answer['status'] == 'completed') {
                                                if('url' in answer) {
                                                    console.log(answer["url"]);
                                                    var url = answer["url"];
                                                    window.location.href = url;
                                                } else if('address' in answer) {
                                                     var address = answer["address"];
                                                     document.getElementById("oc-cc-payment-address").value = address;
                                                     var url_qr_base = 'https://chart.googleapis.com/chart?cht=qr';
                                                     var url_qr_args = '&chs=150';
                                                     url_qr_args += '&choe=UTF8';
                                                     url_qr_args += '&chld=L';
                                                     url_qr_args += "&chl="+address;
                                                     var url_qr = url_qr_base+url_qr_args;
                                                     var url_qr_output = '<img src="'+url_qr+'">';
                                                     document.getElementById("oc_cc_payment_qr_address_container").innerHTML = url_qr_output;
                                                } else {
                                                     document.getElementById("oc-cc-payment-address").value = 'Contacting server...';
                                                }
                                            }
										}
									});
								}
							}
							function checkoutcrypto_order_details(coin_code) {
								if(timeleft > 0) {
								    $.ajax({ 
								        type: 'POST',
								        url: ajaxurl,
								        timeout: 5000,
								        dataType: 'text',
								        data: {
									    action : 'cc_specific_coin',
								            coin_code: coin_code,
								        },
								        error: function() {
								            document.getElementById("cboxLoadedContent").innerHTML += '<div class="warning"><?php echo $error_confirm; ?></div>';
								        },
								        success: function(reply) {
											console.log(reply);
											var answer  = $.parseJSON(reply);
                                            var coin_total = answer["coin_total"];
											var queue_id = answer["queue_id"];
										        if(answer['coin_address'] != 0) {
										             $('#cc_payment_processing_wrapper').show();
										             $("#cc_payment_processing_wrapper").fadeTo("slow", 1.00, function(){ //fade and toggle class
										                 $(this).slideDown("slow");
										                 $(this).toggleClass("oc_cc_hidden");
										             });

										            $("#cc_coin_select_wrapper").fadeTo("slow", 0.00, function(){ //fade and toggle class
										                 $(this).slideUp("slow");
										                 $(this).toggleClass("oc_cc_hidden");
										            });
										            $('#cc_coin_reselect').show();

										             document.getElementById("oc-cc-payment-info").innerHTML = 'Please send <span style="font-weight: bold;"> '+coin_total+'</span> '+coin_code.toUpperCase()+' to:';
										             document.getElementById("oc-cc-payment-address").value = 'Contacting server...';
										             countdown = setInterval(timer, 1000);
										             checker = setInterval(function() { checkoutcrypto_check(coin_code)}, iVal);
										        }
									}
								});
						}
					}
					},
					onCleanup: function() {
						clearInterval(checker);
						checker = 0;
					}
			});  /// colorbox
		} // success
	});  // ajax
});  // button

  if(cc_payment == true) {

    $(document).on("submit", ".wpsc_checkout_forms", function (e) {
        $('#cc-hidden-purchase-btn').click();
        return false;      
    });

     $('#cc-hidden-purchase-btn').click();
  }



 });
