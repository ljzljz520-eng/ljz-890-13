FROM nginx:alpine

# 复制前端文件
COPY public /usr/share/nginx/html
COPY admin /usr/share/nginx/html/admin

# 复制nginx配置
COPY nginx.conf /etc/nginx/conf.d/default.conf

# 创建资源目录
RUN mkdir -p /usr/share/nginx/html/assets/images/gallery

# 暴露端口
EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
