const express = require('express');
const app = express();
var fs = require('fs');
const options = {
    key: fs.readFileSync('/home/server1appsstagi/ssl/keys/eebf9_cc0db_66e7b9989e8e1e00160771372f309eed.key'),
    cert: fs.readFileSync('/home/server1appsstagi/ssl/certs/server1_appsstaging_com_eebf9_cc0db_1701139024_76cfc8c6d2485f6634ac8b8eecb377c4.crt')
};
const server = require('https').createServer(options, app);
// const server = require('http').createServer(app);
const Joi = require('joi');

var io = require('socket.io')(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST", "PATCH", "DELETE"],
        credentials: true,
        transports: ['websocket', 'polling'],
        allowEIO3: false
    },
});

var mysql = require("mysql");

// var con_mysql = mysql.createPool({
//     host: "localhost",
//     user: "root",
//     password: "",
//     database: "m_connect",
//     debug: true,
//     charset: 'utf8mb4'
// });

var con_mysql = mysql.createPool({
    host: "localhost",
    user: "server1appsstagi_m_connect_user",
    password: "}-m}A=90Z5~F",
    database: "server1appsstagi_m_connect_db",
    debug: true,
    charset: 'utf8mb4'
});

// var FCM = require('fcm-node');
// var serverKey = 'AAAAZ3ZrAcE:APA91bFonoDQW__pkxUiPynIyh4cVDRNTCEMYM_PLup_5hDV2KC6exmSeVm1GR1FKr9W8XG8-X8usF8I7tI0EX-ukFoCbvYINBMhLnalth0VBS5NLfHn89qX4o4Xpo2YT5h1URU0GHgl';
// var fcm = new FCM(serverKey);

// SOCKET START
io.on('connection', function (socket) {
    console.log('socket connection *** ', socket.connected)

    socket.on('my_event_request_list', async function (request) {

        const schema = Joi.object({
            event_id: Joi.required(),
            hat_id: Joi.required(),
            type: Joi.required().valid('m_request', 'u_request', 'shout_out_request')
        });

        const { error, value } = schema.validate(request);

        if (error) {
            socket.emit('error', error.details[0].message);
        } else {
            var hat_room = "hat_" + value.type + "_" + value.hat_id;
            socket.join(hat_room);

            try {

                if(value.type == 'u_request'){
                    var response = await get_u_request(value);
                } else if(value.type == 'shout_out_request'){
                    var response = await get_shout_out_request(value);
                } else {
                    var response = [];
                }

                if(response){
                    io.to(hat_room).emit('response', { object_type: value.type, data: response });
                } else {
                    io.to(hat_room).emit('error', { object_type: value.type, message: "There is some problem in " + value.type });
                }    

            } catch (error) {
                io.to(hat_room).emit('error', { object_type: value.type, message: error });
            }
        }

    });

    socket.on('disconnect', function () {
        console.log("Use disconnection", socket.id)
    });
});
// SOCKET END

var get_u_request = async function (request) {
    return new Promise((resolve, reject) => {
        con_mysql.getConnection(function (error, connection) {
            if (error) {
                reject(error);
            } else {
                connection.query(`
                    SELECT 
                        u_requests.id, u_requests.title, u_requests.song, u_requests.thumbnail, u_requests.status, u_requests.created_at,
                        JSON_OBJECT(
                            'id',               users.id,
                            'first_name',       users.first_name,
                            'last_name',        users.last_name,
                            'profile_image',    users.profile_image
                        ) as user
                    FROM u_requests 
                    JOIN users ON users.id = u_requests.user_id
                    WHERE event_id = ${request.event_id} AND deleted_at IS NULL
                    ORDER BY u_requests.id DESC;`, function (error, data) {
                    connection.release();
                    if (error) {
                        reject(error);
                    } else {
                        resolve(data);
                    }
                });
            }
        });
    });
}

var get_shout_out_request = async function (request) {
    return new Promise((resolve, reject) => {
        con_mysql.getConnection(function (error, connection) {
            if (error) {
                reject(error);
            } else {
                connection.query(`
                    SELECT 
                        sor.id, sor.title, sor.receiver, sor.message, sor.seat_number, sor.status, sor.created_at,
                        JSON_OBJECT(
                            'id',               users.id,
                            'first_name',       users.first_name,
                            'last_name',        users.last_name,
                            'profile_image',    users.profile_image
                        ) as user,
                        JSON_OBJECT(
                            'id',      event_types.id,
                            'title',   event_types.title
                        ) as category
                    FROM shout_out_requests as sor
                    JOIN users ON users.id = sor.user_id
                    JOIN event_types ON event_types.id = sor.category_id 
                    WHERE sor.event_id = ${request.event_id} AND sor.deleted_at IS NULL
                    ORDER BY sor.id DESC;`, function (error, data) {
                    connection.release();
                    if (error) {
                        reject(error);
                    } else {
                        resolve(data);
                    }
                });
            }
        });
    });
}

// SERVER LISTENER
server.listen(3009, function () {
    console.log("Server is running on port 3009");
});