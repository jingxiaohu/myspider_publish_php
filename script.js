var tasklist = new Array();

//window.onload = init_page;//不要括号

function TaskInfo() {
    this.task_id = 0;
    this.task_name = '';
    this.start_url = '';
    this.skip_time = 12;
    this.page_list = '';
    this.page_list_times = 0;
    this.page_content = '';
    this.post_title = '';
    this.post_content = '';
    this.post_content_cut = 0;
    this.post_content_cutrule = '';
    this.post_content_start = '';
    this.post_content_end = '';
    this.post_content_add = '';
    this.post_content_image = 1;
    this.post_categray = '';
    this.post_tag = '';
    this.post_writer = 1;
    this.post_publish = 1;
    this.last_time = 0;
    this.catch_nums = 0;
    this.post_content_replace = '';
}

function init_page() {
    document.getElementById("option_detail").style.display = "none";
    //document.getElementById("task_list").style.display="none";
    document.getElementById('option_run_index').style.display = "none";
    document.getElementById('option_run_index').value = "";
    document.getElementById("category_list").style.display = "none";
    document.getElementById("option_post_categray_ids").style.display = "none";

    var taskinfo = document.getElementById('task_list').value;
    tasklist = JSON.parse(taskinfo);
    for (var i = 0; i < tasklist.length; i++) {
        var my_table = document.getElementById("table_list");
        var newTr = my_table.insertRow();
        var newTd0 = newTr.insertCell();
        var newTd1 = newTr.insertCell();
        var newTd2 = newTr.insertCell();
        var newTd3 = newTr.insertCell();
        var newTd4 = newTr.insertCell();
        var newTd5 = newTr.insertCell();
        var newTd6 = newTr.insertCell();
        newTd0.innerText = tasklist[i]['task_id'];
        newTd1.innerText = tasklist[i]['task_name'];
        newTd2.innerText = tasklist[i]['start_url'];
        newTd3.innerText = tasklist[i]['skip_time'];
        newTd4.innerText = tasklist[i]['last_time'];
        newTd5.innerText = tasklist[i]['catch_nums'];
        add_func_list(newTd6);
    }
}

function add_func_list(tNode) {
    var a = document.createElement("a");
    var node = document.createTextNode("删除");
    a.appendChild(node);
    a.setAttribute("href", "#");
    a.setAttribute("class", "col-md-4 text-center");
    a.setAttribute('onClick', "task_delete(this)");
    tNode.appendChild(a);

    a = document.createElement("a");
    node = document.createTextNode("修改");
    a.appendChild(node);
    a.setAttribute("href", "#");
    a.setAttribute("class", "col-md-4 text-center");
    a.setAttribute('onClick', "task_setting(this)");
    tNode.appendChild(a);

    a = document.createElement("a");
    node = document.createTextNode("运行");
    a.appendChild(node);
    a.setAttribute("href", "#");
    a.setAttribute("class", "col-md-4 text-center");
    a.setAttribute('onClick', "task_run(this)");
    tNode.appendChild(a);
}

function show_options() {
    document.getElementById("option_detail").style.display = "";
    document.getElementById("option_task_text").innerText = '任务编号';

    var my_table = document.getElementById("table_list");
    var max_index = 0;
    if(my_table.rows.length > 1)
    {
        max_index = parseInt(my_table.rows[my_table.rows.length - 1].cells[0].innerText);
    }
    document.getElementById("option_task_id").innerText = String(max_index + 1);

    document.getElementById('option_task_name').value = "";
    document.getElementById('option_start_url').value = "";
    document.getElementById('option_skip_time').value = 12;
    document.getElementById('option_page_list').value = "";
    document.getElementById('option_page_list_times').value = 0;
    document.getElementById('option_page_content').value = "";
    document.getElementById('option_post_title').value = "";
    document.getElementById('option_post_content').value = "";
    document.getElementById('option_post_content_cut').value = 0;
    document.getElementById('option_post_content_cutrule').value = "";
    //document.getElementById("option_post_content_cutrule").disabled = true;
    document.getElementById('option_post_content_start').value = "";
    document.getElementById('option_post_content_end').value = "";
    document.getElementById('option_post_content_add').value = "";
    document.getElementById('option_post_content_image').value = 0;
    document.getElementById('option_post_categray_ids').innerText = "";
    document.getElementById('option_post_categray_names').innerText = "自动匹配";
    document.getElementById('option_post_tag').value = "";
    document.getElementById("option_post_content_cutrule_div").style.display = "none";
    document.getElementById('post_content_replace').value = "";
}

function hide_options() {
    document.getElementById("option_detail").style.display = "none";
}

function remove(array, index) {
    if (index <= (array.length - 1)) {
        for (var i = index, j = 0; i < array.length; i++) {
            array[i] = array[i + 1];
        }
    }
    else {
        return array;
    }
    array = array.length - 1;
    return array;
}

function task_delete(src) {
    if (confirm("确定删除吗")) {
        var my_table = document.getElementById("table_list");

        tasklist.splice(src.parentNode.parentNode.rowIndex-1, 1);

        my_table.deleteRow(src.parentNode.parentNode.rowIndex);
        saveOption();
    }
}

function task_setting(src) {
    document.getElementById("option_detail").style.display = "";
    var index = parseInt(src.parentNode.parentNode.rowIndex - 1);
    document.getElementById("option_task_text").innerText = '任务编号';
    document.getElementById("option_task_id").innerText = tasklist[index]['task_id'];


    document.getElementById('option_task_name').value = tasklist[index]['task_name'];
    document.getElementById('option_start_url').value = tasklist[index]['start_url'];
    document.getElementById('option_skip_time').value = tasklist[index]['skip_time'];
    document.getElementById('option_page_list').value = tasklist[index]['page_list'];
    document.getElementById('option_page_list_times').value = tasklist[index]['page_list_times'];
    document.getElementById('option_page_content').value = tasklist[index]['page_content'];
    document.getElementById('option_post_title').value = tasklist[index]['post_title'];
    document.getElementById('option_post_content').value = tasklist[index]['post_content'];
    document.getElementById('option_post_content_cut').value = tasklist[index]['post_content_cut'];
    document.getElementById('option_post_content_cutrule').value = tasklist[index]['post_content_cutrule'];
    if ('0' == tasklist[index]['post_content_cut'])
        document.getElementById("option_post_content_cutrule_div").style.display = "none";
    else
        document.getElementById("option_post_content_cutrule_div").style.display = "";
    document.getElementById('option_post_content_start').value = tasklist[index]['post_content_start'];
    document.getElementById('option_post_content_end').value = tasklist[index]['post_content_end'];
    document.getElementById('option_post_content_add').value = tasklist[index]['post_content_add'];
    document.getElementById('option_post_content_image').value = tasklist[index]['post_content_image'];
    document.getElementById('option_post_categray_ids').innerText = tasklist[index]['post_categray'];
    document.getElementById('option_post_tag').value = tasklist[index]['post_tag'];
    document.getElementById('option_post_writer').value = tasklist[index]['post_writer'];
    document.getElementById('option_post_publish').value = tasklist[index]['post_publish'];
    document.getElementById('option_post_content_replace').value = tasklist[index]['post_content_replace'];

    var items = document.getElementsByTagName("input");
    var ids = tasklist[index]['post_categray'];
    var names = '';
    var strs = new Array();
    strs = ids.split("|");
    for (var i = 0; i < items.length; i++) {
        if (items[i].name == "category_item") {
            if (strs.indexOf(items[i].value) >= 0) {
                items[i].checked = true;
                if (names.length > 0) {
                    names += "|";
                }
                names += items[i].nextSibling.nodeValue;
            } else {
                items[i].checked = false;
            }
        }
    }
    if (0 == length(names))
        names = '自动匹配';
    document.getElementById('option_post_categray_names').innerText = names;
}

function task_run(src) {
    var index = src.parentNode.parentNode.rowIndex - 1;

    document.getElementById('option_run_index').value = index;

    var oBtn = document.getElementById('save-submit-button');
    oBtn.click();
}

function saveRow() {
    var my_table = document.getElementById("table_list");
    var max_index = 0;
    if(my_table.rows.length > 1)
    {
        max_index = parseInt(my_table.rows[my_table.rows.length - 1].cells[0].innerText);
    }

    if (max_index < parseInt(document.getElementById("option_task_id").innerText)) {
        insertRow();
    } else {
        changeRow();
    }
}

function insertRow() {
    var index = tasklist.length;
    tasklist[index] = new TaskInfo();
    tasklist[index]['task_id'] = document.getElementById("option_task_id").innerText;

    tasklist[index]['task_name'] = document.getElementById('option_task_name').value;
    tasklist[index]['start_url'] = document.getElementById('option_start_url').value;
    tasklist[index]['skip_time'] = document.getElementById('option_skip_time').value;
    tasklist[index]['page_list'] = document.getElementById('option_page_list').value;
    tasklist[index]['page_list_times'] = document.getElementById('option_page_list_times').value;
    tasklist[index]['page_content'] = document.getElementById('option_page_content').value;
    tasklist[index]['post_title'] = document.getElementById('option_post_title').value;
    tasklist[index]['post_content'] = document.getElementById('option_post_content').value;
    tasklist[index]['post_content_cut'] = document.getElementById('option_post_content_cut').value;
    tasklist[index]['post_content_cutrule'] = document.getElementById('option_post_content_cutrule').value;
    tasklist[index]['post_content_start'] = document.getElementById('option_post_content_start').value;
    tasklist[index]['post_content_end'] = document.getElementById('option_post_content_end').value;
    tasklist[index]['post_content_add'] = document.getElementById('option_post_content_add').value;
    tasklist[index]['post_content_image'] = document.getElementById('option_post_content_image').value;
    tasklist[index]['post_categray'] = document.getElementById('option_post_categray_ids').innerText;
    tasklist[index]['post_tag'] = document.getElementById('option_post_tag').value;
    tasklist[index]['post_writer'] = document.getElementById('option_post_writer').value;
    tasklist[index]['post_publish'] = document.getElementById('option_post_publish').value;
    tasklist[index]['last_time'] = '--';
    tasklist[index]['catch_nums'] = '0';
    tasklist[index]['post_content_replace'] = document.getElementById('option_post_content_replace').value;

    var my_table = document.getElementById("table_list");
    var newTr = my_table.insertRow();
    var newTd0 = newTr.insertCell();
    var newTd1 = newTr.insertCell();
    var newTd2 = newTr.insertCell();
    var newTd3 = newTr.insertCell();
    var newTd4 = newTr.insertCell();
    var newTd5 = newTr.insertCell();
    var newTd6 = newTr.insertCell();
    newTd0.innerText = tasklist[index]['task_id'];
    newTd1.innerText = tasklist[index]['task_name'];
    newTd2.innerText = tasklist[index]['start_url'];
    newTd3.innerText = tasklist[index]['skip_time'];
    newTd4.innerText = "--";
    newTd5.innerText = "0";

    add_func_list(newTd6);

    hide_options();
    saveOption();
}

function changeRow() {
    var id = document.getElementById('option_task_id').innerText;
    var index = -1;
    for(var i= 0; i < tasklist.length; i++)
    {
        if(tasklist[i]['task_id'] == id)
            index = i;
    }
    if(index == -1)
    {
        return;
    }

    tasklist[index]['task_name'] = document.getElementById('option_task_name').value;
    tasklist[index]['start_url'] = document.getElementById('option_start_url').value;
    tasklist[index]['skip_time'] = document.getElementById('option_skip_time').value;
    tasklist[index]['page_list'] = document.getElementById('option_page_list').value;
    tasklist[index]['page_list_times'] = document.getElementById('option_page_list_times').value;
    tasklist[index]['page_content'] = document.getElementById('option_page_content').value;
    tasklist[index]['post_title'] = document.getElementById('option_post_title').value;
    tasklist[index]['post_content'] = document.getElementById('option_post_content').value;
    tasklist[index]['post_content_cut'] = document.getElementById('option_post_content_cut').value;
    tasklist[index]['post_content_cutrule'] = document.getElementById('option_post_content_cutrule').value;
    tasklist[index]['post_content_start'] = document.getElementById('option_post_content_start').value;
    tasklist[index]['post_content_end'] = document.getElementById('option_post_content_end').value;
    tasklist[index]['post_content_add'] = document.getElementById('option_post_content_add').value;
    tasklist[index]['post_content_image'] = document.getElementById('option_post_content_image').value;
    tasklist[index]['post_categray'] = document.getElementById('option_post_categray_ids').innerText;
    tasklist[index]['post_tag'] = document.getElementById('option_post_tag').value;
    tasklist[index]['post_writer'] = document.getElementById('option_post_writer').value;
    tasklist[index]['post_publish'] = document.getElementById('option_post_publish').value;
    tasklist[index]['post_content_replace'] = document.getElementById('option_post_content_replace').value;
    ;

    var my_table = document.getElementById("table_list");

    my_table.rows[index + 1].cells[1].innerText = tasklist[index]['task_name'];
    my_table.rows[index + 1].cells[2].innerText = tasklist[index]['start_url'];
    my_table.rows[index + 1].cells[3].innerText = tasklist[index]['skip_time'];
    hide_options();
    saveOption();
}

function saveOption() {
    var info = JSON.stringify(tasklist);
    document.getElementById('task_list').value = info;
}

function categoryShow() {
    document.getElementById("category_list").style.display = "";
}

function categorySelect() {
    var items = document.getElementsByTagName("input");
    var names = '';
    var ids = '';
    for (var i = 0; i < items.length; i++) {
        if (items[i].name == "category_item") {
            if (items[i].checked) {
                if (names.length > 0) {
                    names += "|";
                    ids += "|";
                }
                names += items[i].nextSibling.nodeValue;
                ids += items[i].value;
            }
        }
    }
    document.getElementById("option_post_categray_names").innerText = names;
    document.getElementById("option_post_categray_ids").innerText = ids;
    document.getElementById("category_list").style.display = "none";
}

function pageCutChange() {
    var item = document.getElementById("option_post_content_cutrule_div");
    if ("0" == document.getElementById("option_post_content_cut").value) {
        item.style.display = "none";
    } else {
        item.style.display = "";
    }
}

function history_choose_all() {
    var isChecked = document.getElementById("button_choose_all").checked;
    var check_boxes = document.getElementsByClassName("check_box");

    for (var i = 0; i < check_boxes.length; i++) {
        check_boxes[i].checked = isChecked;
    }
}

function delete_history() {
    var check_boxes = document.getElementsByClassName("check_box");
    var ids = document.getElementsByClassName("history_id");

    var delete_ids = '';
    for (var i = 0; i < check_boxes.length; i++) {
        if( true == check_boxes[i].checked ){
            if (delete_ids.length > 0) {
                delete_ids += ",";
            }
            delete_ids += ids[i].innerText;
        }
    }
	document.getElementById('cr_type').value = 1;
    document.getElementById('delete_ids').value = delete_ids;
    document.getElementById('vir_history_form').submit();
}

function fabu_history() {
    var check_boxes = document.getElementsByClassName("check_box");
    var ids = document.getElementsByClassName("history_id");

    var cr_ids = '';
    for (var i = 0; i < check_boxes.length; i++) {
        if( true == check_boxes[i].checked ){
            if (cr_ids.length > 0) {
                cr_ids += ",";
            }
            cr_ids += ids[i].innerText;
        }
    }
	alert("cr_ids="+cr_ids);
	document.getElementById('cr_type').value = 1;
    document.getElementById('cr_ids').value = cr_ids;
    document.getElementById('vir_fabu_form').submit();
}
function fabu_history_all() {
	document.getElementById('cr_type_2').value = 100;
    document.getElementById('vir_fabu_form_all').submit();
}


function history_show_num(num) {
    document.getElementById('delete_ids').value = '';
    document.getElementById('show_page_no').value = 1;
    document.getElementById('show_item_num').value = num;
    document.getElementById('vir_history_form').submit();
}

function history_show_page(num){
    document.getElementById('delete_ids').value = '';
    document.getElementById('show_page_no').value = num;
    document.getElementById('vir_history_form').submit();
}