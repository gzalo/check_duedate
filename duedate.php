<?php

/*
Check_duedate
2019 - Gonzalo Ávila Alterach - gzalo.com
*/

//error_reporting(E_ALL); ini_set('display_errors', 1);

$dataFile = "/store/check_duedate_data";

if(isset($_GET['action'])){
    if($_GET['action'] == 'getData'){
        $data = file_get_contents($dataFile);
        if($data == false)
            die(json_encode(['status'=>1, 'msg'=>"Can't load file $dataFile"]));

        die(json_encode(['data' => json_decode($data,true) ], JSON_FORCE_OBJECT));
    }
    die(json_encode(['status'=>1, 'msg'=>"Unknown action"]));

}else if($_SERVER['REQUEST_METHOD']==='POST' && empty($_POST) ){

    $data = json_decode(file_get_contents("php://input"), true);

    if($data['action'] == 'setData'){
        if(isset($data['data'])){
            $data = file_put_contents($dataFile, $data['data']);
            if($data == false)
                die(json_encode(['status'=>1, 'msg'=>"Can't load file"]));
            die(json_encode(['status'=>0, 'msg'=>"File saved OK"]));
        }else{
            die(json_encode(['status'=>1, 'msg'=>"Missing data parameter"]));
        }
    }
    die(json_encode(['status'=>1, 'msg'=>"Unknown action"]));
}

?><!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check_duedate</title>
    <script src="https://cdn.jsdelivr.net/npm/vue/dist/vue.js"></script>
    <script src="https://unpkg.com/axios/dist/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.24.0/moment-with-locales.min.js"></script>
    <style type="text/css">  
    [v-cloak] { display: none; }
    html,body{
        height: 100%;
        margin:0;
    }
    body{
        font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color:#FFF;
    }
    #app{
        width:350px;
        /*height:200px;*/
        border:1px solid rgba(0,0,0,.05);
        border-radius:15px;
        padding:20px;
        background-color:#FAFAFA;
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
    }
    h1{
        margin: 0;
        margin-bottom:10px;
        text-align:center;
        font-weight:normal;
    }
    .form{
        display:flex;
        flex-direction: column;
    }
    .form input, .form select{
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-bottom:10px;
        padding:5px;
    }
    .form button{
        padding: 8px;
        background-color: #007bff;
        color:#fff;
        border:1px solid transparent;
        border-radius:5px;
        transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        margin-bottom:10px;
        height:35px;
    }
    .form button:focus{
        box-shadow: 0 0 0 3px rgba(0,123,255, .3);
    }
    .form button:disabled{
        background-color:rgba(0,123,255, .3);
    }
    .addNew{
        
        padding-top:10px;
        display:flex;
    }
    .addNew button{
        height:31px;
        flex:1;
    }
    .addNew input{
        flex:2;
        margin-right:8px;
    }
    .error{
        border: 2px solid #f97272;
    }
    hr{
        border: 0;
        border-bottom:1px solid #CCC;
        width:100%;
    }
    </style>
</head>
<body>
<div id="app" v-cloak>
    <div class="wrapper">
        <h1>Due date editor</h1>

        <div class="form">
            <select v-model="selectedObject" @change="recalcSelected()">
                <option value="">Choose an object</option>
                <option v-for="(objectValue, objectName) in objects" :key="objectName" v-bind:value="objectName">{{ objectName }}</option>
            </select>

            <!-- <input type="text" disabled v-if="selectedObject" v-model="selectedTimestamp"/> -->
            <input type="datetime-local" v-if="selectedObject" id="datetime" v-model="selectedDateTime" title="" @change="recalcTimestamp()"/>
            <button @click="saveNewValues" :disabled="pendingChanges == 0">Save new due date</button>
            <hr/>

            <div class="addNew">
                <input type="text" v-model="newName" placeholder="Name" :class="{error: newError}" @keyDown="newError=0"/>
                <button @click="addNewObject">Add new</button>
            </div>
        </div>
    </div>
</div>

<script>

    function timestampToDTL(timestamp){
        return moment.unix(timestamp).utc().local().toISOString(true).split(".")[0];
        //return new Date(timestamp*1000).toISOString().slice(0, -1);
    }
    function DTLToTimestamp(dtl){
        return moment(dtl).format("X");
        //return new Date(dtl).valueOf()/1000;
    }


    var app = new Vue({

        el: '#app',
        data() {
            return {
                objects: {},       
                selectedObject: '',
                selectedTimestamp: '',
                selectedDateTime: '',
                pendingChanges: 0,
                newName: '',
                newError: 0,
            }
        },

        /*watch: {
            objects: {
                deep: true,
                handler: save
            }
        },*/

        mounted () {
            axios
            .get('?action=getData')
            .then(response => { if(response.data.data) this.objects = response.data.data; else alert(response.data.msg); });
        },


        methods: {
            saveNewValues: function(){

                if(this.selectedObject){
                    this.objects[this.selectedObject] = this.selectedTimestamp;
                }

                axios
                .post('?', { action: 'setData', data: JSON.stringify(this.objects) } )
                .then(response => { if(response.data.status == '0') this.pendingChanges = 0; else alert(response.data.msg); } );
            },
            recalcSelected: function(){
                if(this.selectedObject){
                    this.selectedTimestamp = this.objects[this.selectedObject];
                    this.selectedDateTime = timestampToDTL(this.selectedTimestamp);
                }
            },
            recalcTimestamp: function(){
                this.selectedTimestamp = DTLToTimestamp(this.selectedDateTime);
                this.pendingChanges = 1;
            },
            addNewObject: function(){
                if(this.newName && !this.objects[this.newName]){
                    this.objects[this.newName] = moment().format('X');
                    this.selectedObject = this.newName;
                    this.newName = "";
                    this.recalcSelected();
                }else{
                    this.newError = 1;
                }
            }
        },


    });

</script>
</body>
</html>