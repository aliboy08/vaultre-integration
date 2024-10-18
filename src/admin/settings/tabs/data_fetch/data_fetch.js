import './data_fetch.scss';
import { create_element } from 'src/js/utils';

let items_count = {
    processed: null,
    total: null,
}

const items = {
    is_processing: false,
    total: 0,
    process_index: 0,
    current: 0,
    per_batch: 3,
    data: [],
};

console.log('data_fetch.js', items);

let loading_icon;
const results_con = document.querySelector('.vaultre_results');
const buttons = document.querySelectorAll('.vaultre_get_data');

buttons.forEach(button=>{
    button.addEventListener('click',()=>{
        
        loading_icon = create_element('loading_icon');
        button.after(loading_icon);

        init_count();
        get_all_data(button.dataset.last_update);

        buttons.forEach(button=>{
            button.remove();
        })
    });
})

function get_all_data(last_update){

    console.log('get_all_data', last_update)
    
    const endpoints = [
        ["residential", "sale"],
        ["residential", "lease"],
        ["commercial", "sale"],
        ["commercial", "lease"],
        ["rural", "sale"],
        ["business", "sale"],
        ["land", "sale"],
        ["holidayRental", "lease"],
    ];

    let classification, sale_lease;
    
    endpoints.forEach(endpoint=>{

        [classification, sale_lease] = endpoint;

        // let request_url = '/properties/sale?published=true';
        let request_url = `/properties/${classification}/${sale_lease}/available?pagesize=100`;

        if( last_update ) {
            request_url += '&modifiedSince='+ last_update;
        }

        console.log({classification, sale_lease, request_url})

        ajax({ action: 'vaultre_data_fetch', request_url }, (res)=>{

            if( !res.data.urls ) {
                alert(res.data.message);
                return;
            }

            update_total(res);
            update_items(res);
            check_next_page(res);

            if( !items.is_processing ) {
                items.is_processing = true;
                process_items();
            }
        });
    })
}

function init_count(){
    let div = create_element('items_count', results_con);
    div.innerHTML = `
    <span class="current">0</span>
    <span class="sep">/</span>
    <span class="total">0</span>`;
    items_count.processed = div.querySelector('.current');
    items_count.total = div.querySelector('.total');
}

function update_total(res){
    items.total += res.data.totalItems;
    items_count.total.textContent = items.total;
}

function next_page(request_url){
    console.log('next_page', request_url)
    ajax({ action: 'vaultre_data_fetch', request_url }, (res)=>{
        console.log('next_page res', res)
        update_items(res);
        check_next_page(res);

        if( !items.is_processing ) {
            // restart processing items
            console.log('restart processing items');
            items.is_processing = true;
            process_items();
        }
    });
}

function update_items(res){
    if( !res.data.items ) return;
    items.data = items.data.concat(res.data.items);
}

function check_next_page(res){
    
    console.log('check_next_page', res)
    
    if( !res.data.urls ) {
        alert(res.data.message);
        return;
    }

    if( res.data.urls.next ) {
        next_page(res.data.urls.next);
    }
}

function process_items(){

    console.log('process_items start');

    let items_to_process = get_items_to_process();

    let data = {
        action: 'vaultre_process_items',
        items: items_to_process
    }

    if( items.process_index >= items.total ) {
        data.is_last = true;
    }

    ajax(data, (res)=>{
        console.log('process_items res', res)
        process_items_next();
    });
}

function process_items_next(){

    items.current = items.process_index;
    items_count.processed.textContent = items.process_index;

    console.log('process_items_next', items)

    if( items.current < items.data.length ) {
        process_items();
    }
    else {
        // complete
        console.log('process items complete');
        items.is_processing = false;
        loading_complete();
    }
}

function get_items_to_process(){

    let items_batch = [];

    for( let i = 0; i < items.per_batch; i++ ) {
        items_batch.push(items.data[items.process_index]);
        items.process_index++;
        if( items.process_index == items.data.length || items.process_index == items.total ) {
            console.log('end batches');
            break;
        }
    }

    return items_batch;
}

function loading_complete(){
    loading_icon.remove();
}

function ajax(data, cb){
    jQuery.ajax({
        url: ajaxurl,
        type: "post",
        data,
        dataType : "json",
    })
    .done(function(res){
        cb(res);
    })
}