<?php get_header();
/*
Template Name: Single Exchanges
*/
?>
<?php
$ads = get_field('ads');
?>
<!-- hype loot section  -->
<section class="hype_loot text-center ">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                    <div><img src="<?php echo $ads[0]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[0]['text'];?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- hype loot section end -->



      <section class="scroll_banner">
         <div class="container">
            <div class="row">
               <div class="col-md-8">
                  <h2 class="global_heading"><span class="yellow" id="cypto_name"></span></h2>
                  <!-- <p class="mb-0 fs-20 white">The most complex and detailed market data, including sentiment, news, comparisons and social metrics on Cryptocurrencies, Exchanges and NFTs.</p> -->
               </div>
            </div>
         </div>
      </section>



      <!-- hot section  -->
      <section class="hots py-5">
         <div class="container">
             
            <div class="row">
               <div class="col-lg-3 col-md-4">
                     <?php get_template_part('page-templates/components/page-sidebar'); ?>
               </div>
               <div class="col-lg-9 col-md-8 mb-25">
                 
    <div class="crypto_table mb-4" style="overflow-x: auto;">
                     <table class="table " id="table_single_exchange">
                        <thead>
                           <tr class="table-head">
                             <td>Market</td>
                                <td>Year</td>
                                <td>24h Volume</td>
                                <td>Trust Score/Rank</td>
                           </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                     </table>
                  </div>


               <!-- exchange row  -->
               <div>
                  <h4 class=" mb-0 mb-25 bold mb-3">Trading cryptocurrencies
                  </h4>
                  <div class="crypto_table mb-4" style="overflow-x: auto;">
                     <table class="table " id="table_exchange">
                        <thead>
                           <tr class="table-head">
                              <td>Rank</td>
                              <td>Coin</td>
                              <td>Pair</td>
                              <td>Price</td>
                              <td>Bid/Ask spread</td>
                              <td>24h trading volume</td>
                              <td>Volume</td>
                           </tr>
                        </thead>
                        <tbody>       
                        </tbody>
                     </table>
                  </div>
               </div>
               <!-- exchange row end -->
             

               <div class="hypeloot_casino mb-4 text-center">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="" class="white">
                                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                                <div><img src="<?php echo $ads[1]['image'];?>" class="img-fluid bydefi " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[1]['text'];?></p>
                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                   

 

                  <div class="row charts_row mb-4">
                     <div class="col-md-12  mb-3">
                        <h4 class=" mb-0 mb-25 bold">Overview
                        </h4>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Exchange volume</h4>
                           </div>
                           <div class="mb-4  " id="exchange_volume" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Exchange trading volume</h4>
                           </div>
                           <div class="mb-4  " id="exchange_trading_volume" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                  </div>
                
                   

               <div class="hypeloot_casino mb-4 text-center">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="" class="white">
                                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                                <div><img src="<?php echo $ads[2]['image'];?>" class="img-fluid bydefi " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[2]['text'];?></p>
                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
                 


                  <div class="row charts_row mb-4">
                     <div class="col-md-12  mb-3">
                        <h4 class=" mb-0 mb-25 bold">Overview
                        </h4>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Top pairs by volume</h4>
                           </div>
                           <div class="mb-4  " id="top_pairs_chart" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Top pairs bid/ask percentage</h4>
                           </div>
                           <div class="mb-4  " id="ask_percentage" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                  </div>



               <div class="hypeloot_casino mb-4 text-center">
                    <div class="row">
                        <div class="col-md-12">
                            <a href="" class="white">
                                <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                                <div><img src="<?php echo $ads[3]['image'];?>" class="img-fluid bydefi " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[3]['text'];?></p>
                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>

  
               </div>
            </div>
         </div>
      </section>

      <!-- hot section end -->
     <script>
            function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }
    jQuery(document).ready(function($) {
        // const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=10&page=1&sparkline=false';
        const exchangeApiUrl = 'https://api.coingecko.com/api/v3/exchanges';

        const options = {
            method: 'GET',
            headers: {
                accept: 'application/json',
                'x-cg-demo-api-key': 'CG-aJuTDgTp1nYxkH8hQ6EGrYjj'
            }
        };
 

        function updateExchangeData(exchangeName) {
    const exchangeApiUrl = `https://api.coingecko.com/api/v3/exchanges/${exchangeName}`;
    
    $.ajax({
        url: exchangeApiUrl,
        type: 'GET',
        success: function(data) {
            let tableBody = '';

            if (data && data.tickers && data.tickers.length > 0) {
               $('#cypto_name').text(data.name)
                const tickers = data.tickers;
                tickers.sort((a, b) => b.converted_volume.usd - a.converted_volume.usd); // Sort by 24h trading volume in USD

                tickers.slice(0, 50).forEach((ticker, index) => {
                    const volume24h = ticker.converted_volume.usd || 0;
                    const pair = `${ticker.base}/${ticker.target}`;
                    const price = ticker.converted_last.usd || 0;
                    const bidAskSpread = ticker.bid_ask_spread_percentage || 0;
                    const trustScore = data.trust_score || 'N/A';

                    tableBody += `
                        <tr>
                            <td><span class="crypto_rank">${index + 1}</span></td>
                            <td><span>${ticker.base}</span></td>
                            <td><span>${pair}</span></td>
                            <td>$${price.toFixed(2)}</td>
                            <td>${bidAskSpread.toFixed(2)}%</td>
                            <td>$${(volume24h / 1e6).toFixed(2)} M</td>
                            <td>${ticker.volume.toFixed(2)}</td>
                        </tr>
                    `;
                });

                $('#table_exchange tbody').html(tableBody);
            } else {
                console.log('No data available for the specified exchange.');
            }
        },
        error: function(error) {
            console.error('Error fetching data from CoinGecko API:', error);
        }
    });
}

        // Update the data when the document is ready
        const cryptoId = getQueryParam('id');
        updateExchangeData(cryptoId);
         
    });

 


    jQuery(document).ready(function() {
    jQuery('#search_coin').keyup(function() {
        console.log('click')
        let query = jQuery(this).val();
        if (query.length > 0) {
            jQuery.ajax({
                url: `https://api.coingecko.com/api/v3/search?query=${query}`,
                method: 'GET',
                success: function(data) {
                    let resultsContainer = jQuery('#search-results');
                    resultsContainer.empty();
                    jQuery.each(data.coins, function(index, coin) {
                        let link = jQuery('<a></a>').attr('href', `/data/cryptocurrency/${coin.id}`).html(`<span>${coin.name}</span>`);
                        resultsContainer.append(link);
                    });
                }
            });
        } else {
            jQuery('#search-results').empty();
        }
    });

    jQuery('#close-results').click(function() {
        jQuery('#search-results').empty();
        jQuery('#search_coin').val('');
    });
});

async function topPairsByVolume(exchangeId) {
            $.ajax({
                url: `https://api.coingecko.com/api/v3/exchanges/${exchangeId}`,
                method: 'GET',
                success: function(data) {
                    var pairsData = data.tickers.map(function(ticker) {
                        return { label: `${ticker.base}/${ticker.target}`, y: ticker.volume };
                    });

                    // Sort pairs by volume in descending order and take the top 10 pairs
                    pairsData.sort((a, b) => b.y - a.y);
                    pairsData = pairsData.slice(0, 10);

                    var chart = new CanvasJS.Chart("top_pairs_chart", {
                        theme: "light2",
                        title: {
                            text: null
                        },
                        data: [{
                            type: "pie",
                            showInLegend: false,
                            indexLabel: "{label} - #percent%",
                            dataPoints: pairsData
                        }]
                    });

                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }
         
        async function topPairsByBidAsk(exchangeId) {
            $.ajax({
                url: `https://api.coingecko.com/api/v3/exchanges/${exchangeId}`,
                method: 'GET',
                success: function(data) {
                    var pairsData = data.tickers.map(function(ticker) {
                        var bidAskPercentage = ((ticker.bid_ask_spread_percentage) * 100).toFixed(2);
                        return { label: `${ticker.base}/${ticker.target}`, y: parseFloat(bidAskPercentage) };
                    });

                    // Sort pairs by bid/ask percentage in descending order and take the top 10 pairs
                    pairsData.sort((a, b) => b.y - a.y);
                    pairsData = pairsData.slice(0, 10);

                    var chart = new CanvasJS.Chart("ask_percentage", {
                        theme: "light2",
                        title: {
                            text: null
                        },
                        data: [{
                            type: "pie",
                            showInLegend: false,
                            indexLabel: "{label} - {y}%",
                            dataPoints: pairsData
                        }]
                    });

                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }
        async function exchangeVolume() {
            $.ajax({
                url: `https://api.coingecko.com/api/v3/exchanges`,
                method: 'GET',
                success: function(data) {
                    var volumeData = data.map(function(exchange) {
                        return { label: exchange.name, y: exchange.trade_volume_24h_btc };
                    });

                    // Sort exchanges by volume in descending order and take the top 10
                    volumeData.sort((a, b) => b.y - a.y);
                    volumeData = volumeData.slice(0, 10);

                    var chart = new CanvasJS.Chart("exchange_volume", {
                        theme: "light2",
                        title: {
                            text: null
                        },
                        data: [{
                            type: "pie",
                            showInLegend: false,
                            indexLabel: "{label} - #percent%",
                            dataPoints: volumeData
                        }]
                    });

                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }

        async function exchangeTradingVolume() {
            $.ajax({
                url: `https://api.coingecko.com/api/v3/exchanges`,
                method: 'GET',
                success: function(data) {
                    var volumeData = data.map(function(exchange) {
                        return { label: exchange.name, y: exchange.trade_volume_24h_btc_normalized };
                    });

                    // Sort exchanges by volume in descending order and take the top 10
                    volumeData.sort((a, b) => b.y - a.y);
                    volumeData = volumeData.slice(0, 10);

                    var chart = new CanvasJS.Chart("exchange_trading_volume", {
                        theme: "light2",
                        title: {
                            text: null
                        },
                        data: [{
                            type: "pie",
                            showInLegend: false,
                            indexLabel: "{label} - #percent%",
                            dataPoints: volumeData
                        }]
                    });

                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }
        async function getUsdPrice() {
            return $.ajax({
                url: 'https://api.coingecko.com/api/v3/exchange_rates',
                type: 'GET'
            });
        }
     async function singleExchangeData(exchangeId) {
		 const exchangeApiUrl = `https://api.coingecko.com/api/v3/exchanges/${exchangeId}`;
         const usdResponse = await getUsdPrice();
         const usdValue = usdResponse.rates.usd.value;
             $.ajax({
                url: exchangeApiUrl,
                success: function(exchange) {
                  
                    let tableBody = '';

						volume24h = exchange.trade_volume_24h_btc * usdValue;
                        tableBody += `
                        <tr onclick="window.location='/data/exchanges-single?id=${exchange.id}'">
                           
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${exchange.image}" class="coin_img" alt="${exchange.name}" style="width: 50px; height: 50px;">
                                    <span class="coin_name fw-bold">${exchange.name}</span>
                                </div>
                            </td>
                             <td>${exchange.year_established}</td>
                            <td>$${volume24h}</td>
                            <td>${exchange.trust_score}/${exchange.trust_score_rank}</td>
                        </tr>
                        `;
                       
						
						
                   
                    $('#table_single_exchange tbody').html(tableBody);
					
					
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
    }
		 
setTimeout(() => {
         const exchangeId = getQueryParam('id');
	
            singleExchangeData(exchangeId);
            topPairsByVolume(exchangeId);
            topPairsByBidAsk(exchangeId);
            exchangeVolume();
            exchangeTradingVolume();
        }, 1000);


</script>

<?php get_footer(); ?>