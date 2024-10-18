export function create_element( class_name = null, append_to = null, text = null, tag = 'div' ){
    let el = document.createElement(tag);
    if( class_name ) el.className = class_name;
    if( text ) el.textContent = text;
    if( append_to ) append_to.append(el);
    return el;
}