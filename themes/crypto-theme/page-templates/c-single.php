<?php get_header();
/*
Template Name: Single Crypto
*/
?>
<?php
$ads = get_field('ads');
?>
<style>
	.green{
		color:green !important;
	}
</style>
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
                     <table class="table " id="table_crypto">
                        <thead>
                           <tr class="table-head">
                              <td>Rank</td>
                              <td>Cryptocurrency</td>
                              <td>Price</td>
                              <td>7 days change</td>
                              <td>24h trading volume</td>
                              <td>Marketcap</td>
                           </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                     </table>
                  </div>



               <!-- exchange row  -->
               <div>
                  <h4 class=" mb-0 mb-25 bold mb-3">Exchanges
                  </h4>
                  <div class="crypto_table mb-4" style="overflow-x: auto;">
                     <table class="table " id="table_exchange">
                        <thead>
                           <tr class="table-head">
                                  <td>Rank</td>
                                 <td>Market</td>
                                 <td>24h volume	</td>
                                 <td>Score</td>
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
                              <h4 class="fw-bold mb-0 ">Price</h4>
                           </div>
                           <div class="mb-4  " id="single_price_chart" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Rank</h4>
                           </div>
                           <div class="mb-4  " id="single_rank_chart" style="height: 300px; width: 100%;"></div>


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
                              <h4 class="fw-bold mb-0 ">Marketcap</h4>
                           </div>
                           <div class="mb-4  " id="single_market_chart" style="height: 300px; width: 100%;"></div>


                          <div class="text-end">
                           <button class="btn-primary-small border-0 "><i class="bi bi-download"></i></button>
                          </div>
                         
                          
                        </div>
                     </div>
                     <div class="col-md-6">
                        <div class="charts_grid">
                           <div class="mb-3">
                              <h4 class="fw-bold mb-0 ">Trading volume</h4>
                           </div>
                           <div class="mb-4  " id="volume_chart" style="height: 300px; width: 100%;"></div>


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

        function updateCryptoData(cryptoName) {
             
            const cryptoApiUrl = `https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids=${cryptoName}&order=market_cap_desc&per_page=10&page=1&sparkline=false`;
            $.ajax({
                url: cryptoApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
                    let tableBody = '';
                    if (data.length > 0) {
                const coin = data[0];
               $('#cypto_name').text(coin.name)
                const priceChange7d = coin.price_change_percentage_7d_in_currency || 0;
                const priceChange24h = coin.price_change_percentage_24h || 0;
                const totalVolume = coin.total_volume || 0;
                const marketCap = coin.market_cap || 0;

                        tableBody += `
                        <tr onclick="window.location='/data/cryptocurrency-single?id=${coin.id}'">
                            <td><span class="crypto_rank">${coin.market_cap_rank}</span></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${coin.image}" class="coin_img" alt="${coin.name}">
                                    <span class="coin_name fw-bold">${coin.name}</span>
                                    <span class="coin_symbol">${coin.symbol.toUpperCase()}</span>
                                </div>
                            </td>
                            <td><span class="coin-price-dp">$${coin.current_price}</span></td>
                               <td>
                                <div class="coin_price_range d-flex align-items-center gap-1">
                                    ${priceChange24h >= 0 ? 
                                        ` 
                                        <p class="mb-0"><i class="fa fa-caret-up green"></i> <span class="green">${priceChange24h.toFixed(2)}%</span></p>` :
                                        ` 
                                        <p class="mb-0"><i class="fa fa-caret-down red"></i> <span class="red">${priceChange24h.toFixed(2)}%</span></p>`
                                    }
                                </div>
                            </td>
                            <td><span>$${(totalVolume / 1e9).toFixed(2)} B</span></td>
                            <td><span>$${(marketCap / 1e12).toFixed(2)} T</span></td>
                        </tr>
                    `;
                    $('#table_crypto tbody').html(tableBody);
                    }else {
                console.log('No data available for the specified cryptocurrency.');
            }
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

        function updateExchangeData() {
            $.ajax({
                url: exchangeApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
                    data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
                    let tableBody = '';
                    data.slice(0, 10).forEach((exchange, index) => {
                        const volume24h = exchange.trade_volume_24h_btc || 0;
                        const numberOfCoins = exchange.coins || 0;
                        const numberOfPairs = exchange.pairs || 0;

                        tableBody += `
                         <tr onclick="window.location='/data/exchanges-single?id=${exchange.id}'">
                        <td><span class="crypto_rank">${exchange.trust_score_rank}</span></td>
                        <td>
                                <div class="d-flex align-items-center gap-1">
                                    <img src="${exchange.image}" class="coin_img" alt="Bitcoin">
                                    <span class="coin_name fw-bold">${exchange.name}</span>
                                     
                                </div>
                            </td>
                            
                            <td>$${(volume24h * 1e6).toFixed(2)} </td>
                            <td>${exchange.trust_score}</td>
                           
                             
                        </tr>
                    `;
                    });
                    $('#table_exchange tbody').html(tableBody);
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

        // Update the data when the document is ready
        const cryptoId = getQueryParam('id');
        updateCryptoData(cryptoId);
        updateExchangeData();
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
</script>
 
<script>
        async function priceGraph(cryptoId) {
            $.ajax({
                url: `https://api.coingecko.com/api/v3/coins/${cryptoId}/market_chart`,
                method: 'GET',
                data: {
                    vs_currency: 'usd',
                    days: 365
                },
                success: function(data) {
                    var chartData = data.prices.map(function(price) {
                        return { x: new Date(price[0]), y: price[1] };
                    });

                    var chart = new CanvasJS.Chart("single_price_chart", {
                        theme: "light2",
                        title: null,
                        data: [{
                            type: "line",
                            dataPoints: chartData
                        }]
                    });

                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }
        async function marketGraph(cryptoId) {
         $.ajax({
                url: `https://api.coingecko.com/api/v3/coins/${cryptoId}/market_chart`,
                method: 'GET',
                data: {
                    vs_currency: 'usd',
                    days: 365
                },
                success: function(data) {
                    var rankData = data.market_caps.map(function(marketCap) {
                        return { x: new Date(marketCap[0]), y: marketCap[1] };
                    });

                    var options = {
                        theme: "light2",
                        title: null,
                        data: [{
                            type: "line",
                            dataPoints: rankData
                        }]
                    };

                    var chart = new CanvasJS.Chart("single_market_chart", options);
                    chart.render();
                },
                error: function(xhr, status, error) {
                    console.log('Error fetching data:', error);
                }
            });
        }

        async function rankGraph(cryptoId) {
    $.ajax({
        url: `https://api.coingecko.com/api/v3/coins/${cryptoId}/market_chart`,
        method: 'GET',
        data: {
            vs_currency: 'usd',
            days: 365
        },
        success: function(data) {
            // Replace this section with actual rank data retrieval if available
            // Simulating rank data for demonstration
            var rankData = data.prices.map(function(priceData, index) {
                // Simulating rank: for example purposes only
                // Assume priceData[0] is the timestamp and priceData[1] is the price
                var rank = 1000 - index; // This should be replaced with actual rank data
                return { x: new Date(priceData[0]), y: rank };
            });

            var options = {
                theme: "light2",
                title: {
                    text: null
                },
                axisY: {
                    title: "Rank",
                    reversed: true // Lower values should be at the top
                },
                data: [{
                    type: "line",
                    dataPoints: rankData
                }]
            };

            var chart = new CanvasJS.Chart("single_rank_chart", options);
            chart.render();
        },
        error: function(xhr, status, error) {
            console.log('Error fetching data:', error);
        }
    });
}

async function volumeGraph(cryptoId) {
    $.ajax({
        url: `https://api.coingecko.com/api/v3/coins/${cryptoId}/market_chart`,
        method: 'GET',
        data: {
            vs_currency: 'usd',
            days: 365
        },
        success: function(data) {
            var volumeData = data.total_volumes.map(function(volume) {
                return { x: new Date(volume[0]), y: volume[1] };
            });

            var options = {
                theme: "light2",
                title: {
                    text: null
                },
                axisY: {
                    title: "Volume (USD)",
                },
                data: [{
                    type: "line",
                    dataPoints: volumeData
                }]
            };

            var chart = new CanvasJS.Chart("volume_chart", options);
            chart.render();
        },
        error: function(xhr, status, error) {
            console.log('Error fetching data:', error);
        }
    });
}
        
        setTimeout(() => {
         const cryptoId = getQueryParam('id');
            priceGraph(cryptoId);
            marketGraph(cryptoId);
            rankGraph(cryptoId);
            volumeGraph(cryptoId);
        }, 1000);
    </script>
<?php get_footer(); ?>