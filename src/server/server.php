<?php
    // 若存在用户信息文件，先清空
    if (file_exists(__DIR__ . '/user.json')) {
      file_put_contents(__DIR__ . '/user.json', json_encode(array()));
    }

    $ws = new swoole_websocket_server("0.0.0.0", 9501);

    $ws->on('open', function ($ws, $request) {
        // 将当前用户信息、在线用户信息推送给当前用户本人
        $dataArr = array();
        if (file_exists(__DIR__ . '/user.json')) {
          $userArr = json_decode(file_get_contents(__DIR__ . '/user.json'), true);
          foreach ($userArr as $key => $value) {
            if ($value['status'] == 1) {
              $dataArr[] = array(
                'fd' => $value['fd'],
                'nickname' => $value['nickname']
              );
            }
          }
        }
        $ws->push($request->fd, json_encode(array('type' => 0, 'fd' => $request->fd, 'nickname' => $request->get['nickname'], 'data' => $dataArr)));

        // 将用户写入文件
        $userArr = array();
        if (file_exists(__DIR__ . '/user.json')) {
            $userArr = json_decode(file_get_contents(__DIR__ . '/user.json'), true);
            $has = false;
            foreach ($userArr as $key => $value) {
                if ($value['fd'] == $request->fd) {
                    $userArr[$key]['fd'] = $request->fd;
                    $userArr[$key]['nickname'] = $request->get['nickname'];
                    $userArr[$key]['status'] = 1; // 0：离线 1：在线
                    $has = true;
                    break;
                }
            }
    
            if (!$has) {
                $userArr[] = array(
                  'fd' => $request->fd,
                  'nickname' => $request->get['nickname'],
                  'status' => 1
                );
            }
        } else {
          $userArr[] = array(
            'fd' => $request->fd,
            'nickname' => $request->get['nickname'],
            'status' => 1
          );
        }
        $res = file_put_contents(__DIR__ . '/user.json', json_encode($userArr));

        // 将当前新加入用户信息及消息推送给所有在线用户
        foreach ($userArr as $key => $value) {
            if ($value['status'] == 1) {
                $ws->push($value['fd'], json_encode(array('type' => 1, 'fd' => $request->fd, 'nickname' => $request->get['nickname'])));
            }
        }
    });

    $ws->on('message', function ($ws, $frame) {
        $userArr = array();
        if (file_exists(__DIR__ . '/user.json')) {
            $userArr = json_decode(file_get_contents(__DIR__ . '/user.json'), true);
            $nickname = '';
            // 遍历所有用户，获取当前用户的昵称
            foreach ($userArr as $key => $value) {
                if ($value['fd'] == $frame->fd) {
                    $nickname = $value['nickname'];
                    break;
                }
              
            }

            // 将当前用户信息及消息推送给所有在线用户
            foreach ($userArr as $key => $value) {
                if ($value['status'] == 1) {
                    $ws->push($value['fd'], json_encode(array('type' => 2, 'fd' => $frame->fd, 'nickname' => $nickname, 'data' => $frame->data)));
                }
            }
        }
    });

    $ws->on('close', function ($ws, $fd) {
        $userArr = array();
        if (file_exists(__DIR__ . '/user.json')) {
            $userArr = json_decode(file_get_contents(__DIR__ . '/user.json'), true);
            $nickname = '';
            // 遍历所有用户，获取当前用户的昵称并将当前用户状态改为离线
            foreach ($userArr as $key => $value) {
                if ($value['fd'] == $fd) {
                    $nickname = $value['nickname'];
                    $userArr[$key]['status'] = 0;
                    break;
                }
            }
            $res = file_put_contents(__DIR__ . '/user.json', json_encode($userArr));

            // 将当前用户信息及离线状态推送给所有在线用户
            foreach ($userArr as $key => $value) {
                if ($value['status'] == 1) {
                    $ws->push($value['fd'], json_encode(array('type' => 3, 'fd' => $fd, 'nickname' => $nickname)));
                }
            }
        }
    });

    $ws->start();