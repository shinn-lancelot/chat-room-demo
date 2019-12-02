## chat-room-demo

> A simple chat room demo. 一个简单的聊天室demo。

### 如何运行

1. 启动后端WebSocket服务（本项目后端WebSocket服务通过PHP的swoole实现）

```shell
  $ php src/server/server.php
```

2. 启动客户端（可用php内置服务器，或nginx等等，方法有很多，端口可自行修改）

```shell
  $ php -S 127.0.0.1:1180 src/client/index.html
```

### 备注

若无法正常使用，请检查是否需要修改客户端及服务端的ip