<?php get_header();
/*
Template Name: Data
*/
?>
<style>
	.green {
    color: #73eab1;
}
</style>
   <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.21/css/jquery.dataTables.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <!-- DataTables JS -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.21/js/jquery.dataTables.js"></script>
    <?php
$ads = get_field('ads');
?>
      <!-- hype loot section  -->
      <section class="hype_loot text-center ">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
				   <a href="<?php echo $ads[0]['ads_url'];?>">
                  <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2 ">
                  <div><img src="<?php echo $ads[0]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[0]['text'];?></p>
                    </div>
                  </div>
				   </a>
               </div>
            </div>
         </div>
      </section>
      <!-- hype loot section end -->
      <!-- hot section  -->
      <section class="hots py-5">
         <div class="container">
            <div class="row pb-5">
               <div class="col-md-8">
               <h2 class="global_heading"><span class="yellow"><?php echo the_field('title');?></span></h2>
               <p class="mb-0 fs-20"><?php echo the_field('content');?></p>
               </div>
            </div>
            <div class="row">
               <div class="col-lg-3 col-md-4">
                  <?php get_template_part('page-templates/components/page-sidebar'); ?>
               </div>
               <div class="col-lg-9 col-md-8 mb-25">
                  <h3 class="fw-bold mb-4"><span class="yellow"><?php the_field('comparisons_yellow_heading'); ?></span> <?php the_field('comparisons_black_heading'); ?></h3>
                  <div class="row compare_row mb-4">
                     <div class="col-sm-12 col-md-12 col-lg-12 col-xl-4 mb-xl-25">
                        <a href="">
                           <div class="compare_grid text-center">
                              <div class="row align-items-center justify-content-center  ">
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('first_left_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('first_left_currency_name'); ?></p>
                                    </div>
                                 </div>
                                 <div class="col-2">
                                    <p class="mb-0 white fw-bold  ">Vs</p>
                                 </div>
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('first_right_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('first_right_currency_name'); ?></p>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </a>
                     </div>
                     <div class="col-sm-12 col-md-12 col-lg-12 col-xl-4 mb-xl-25">
                        <a href="">
                           <div class="compare_grid text-center justify-content-center ">
                              <div class="row align-items-center  justify-content-center">
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('second_left_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('second_left_currency_name'); ?></p>
                                    </div>
                                 </div>
                                 <div class="col-2">
                                    <p class="mb-0 white fw-bold  ">Vs</p>
                                 </div>
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('second_right_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('second_right_currency_name'); ?></p>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </a>
                     </div>
                     <div class="col-sm-12 col-md-12 col-lg-12 col-xl-4 mb-xl-25">
                        <a href="">
                           <div class="compare_grid text-center justify-content-center ">
                              <div class="row align-items-center justify-content-center ">
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('third_left_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('third_left_currency_name'); ?></p>
                                    </div>
                                 </div>
                                 <div class="col-2">
                                    <p class="mb-0 white fw-bold  ">Vs</p>
                                 </div>
                                 <div class="col-5">
                                    <div class="d-flex align-items-center gap-1 justify-content-center ">
                                       <div><img src="<?php the_field('third_right_currency_image'); ?>" class="img-fluid " alt=""></div>
                                       <p class="mb-0 white fw-bold  "><?php the_field('third_right_currency_name'); ?></p>
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </a>
                     </div>
                  </div>
                  <div class="hypeloot_casino mb-4 text-center">
                     <div class="row">
                        <div class="col-md-12">
                           <a href="<?php echo $ads[1]['ads_url'];?>" class="white">
                              <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                              <div><img src="<?php echo $ads[1]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[1]['text'];?></p>
                    </div>
                              </div>
                           </a>
                        </div>
                     </div>
                  </div>
                  <div class="d-inline-block d-md-flex  justify-content-between align-items-center mb-3 ">
                     <h4 class=" mb-0 mb-25">Cryptocurrencies
                     </h4>
                     <!-- <div>
                        <a href="" class="btn btn-secondary ">View data</a>
                     </div> -->
                  </div>
                  <div class="crypto_table mb-4" style="overflow-x: auto;">
                     <table class="table " id="table_crypto">
                        <thead>
                           <tr class="table-head">
                              <td>Rank</td>
                              <td>Cryptocurrency</td>
                              <td>Price</td>
                              <td>24h Price change</td>
                              <td>24h trading volume</td>
                              <td>Marketcap</td>
                           </tr>
                        </thead>
                        <tbody>
                   
                        </tbody>
                     </table>
                  </div>
                  <div class="hypeloot_casino mb-4 text-center">
                     <div class="row">
                        <div class="col-md-12">
                           <a href="<?php echo $ads[2]['ads_url'];?>" class="white">
                              <div class="d-inline-block d-md-flex text-center  justify-content-center align-items-center gap-2  ">
                              <div><img src="<?php echo $ads[2]['image'];?>" class="img-fluid hype_loots " alt=""></div>
                    <div>
                        <p class="m-0 "><?php echo $ads[2]['text'];?></p>
                    </div>
                              </div>
                           </a>
                        </div>
                     </div>
                  </div>
				   <hr>
                  <div class="d-inline-block d-md-flex  justify-content-between align-items-center mb-3 ">
                     <h4 class=" mb-0 mb-25">Exchanges
                     </h4>
                     <!-- <div>
                        <a href="" class="btn btn-secondary ">View data</a>
                     </div> -->
                  </div>
                  <div class="grid_radiuss ">
                     <div class="d-inline-block d-md-flex  justify-content-between align-items-center mb-3 ">
                        <h5 class="white mb-0 mb-25">Top 10 Exchanges
                        </h5>
                        <!-- <div>
                           <a href="" class="btn btn-primary-small me-2 ">SPOT</a>
                           <a href="" class="btn btn-secondary-small ">DEX</a>
                        </div> -->
                     </div>
                     <div class="crypto_table mb-4" style="overflow-x: auto;">
                        <table class="table" id="table_exchange">
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
                  </div>
               </div>
            </div>
         </div>
      </section>
      <!-- hot section end -->
  <script>
   jQuery(document).ready(function($) {
    const cryptoApiUrl = 'https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&order=market_cap_desc&per_page=100&page=1&sparkline=false';
    const exchangeApiUrl = 'https://api.coingecko.com/api/v3/exchanges';

    const options = {
        method: 'GET',
        headers: {
            accept: 'application/json',
            'x-cg-demo-api-key': 'CG-aJuTDgTp1nYxkH8hQ6EGrYjj'
        }
    };

    function updateCryptoData() {
        $.ajax({
            url: cryptoApiUrl,
            type: options.method,
            headers: options.headers,
            success: function(data) {
                let tableBody = '';
                data.forEach((coin, index) => {
                    const priceChange7d = coin.price_change_percentage_7d_in_currency || 0;
                    const priceChange24h = coin.price_change_percentage_24h || 0;
                    const totalVolume = coin.total_volume || 0;
                    const marketCap = coin.market_cap || 0;

                    tableBody += `
                        <tr onclick="window.location='/data/cryptocurrency-single?id=${coin.id}'">
                            <td><span class="crypto_rank">${index + 1}</span></td>
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
                });
                $('#table_crypto tbody').html(tableBody);
                $('#table_crypto').DataTable();
            },
            error: function(error) {
                console.error('Error fetching data from CoinGecko API:', error);
            }
        });
    }
        async function getUsdPrice() {
            return $.ajax({
                url: 'https://api.coingecko.com/api/v3/exchange_rates',
                type: 'GET'
            });
        }
               async function updateExchangeData() {
            const usdResponse = await getUsdPrice();
            const usdValue = usdResponse.rates.usd.value;
            $.ajax({
                url: exchangeApiUrl,
                type: options.method,
                headers: options.headers,
                success: function(data) {
					
                    data.sort((a, b) => b.trade_volume_24h_btc - a.trade_volume_24h_btc);
                    let tableBody = '';
                    data.slice(0, 25).forEach((exchange, index) => {
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
                       
						
						
                    });
                    $('#table_exchange tbody').html(tableBody);
					$('#table_exchange').DataTable();
					
                },
                error: function(error) {
                    console.error('Error fetching data from CoinGecko API:', error);
                }
            });
        }

    // Update the data when the document is ready
    updateCryptoData();
    updateExchangeData();
});

  </script>
<?php get_footer();  ?>