FROM nginx:1.21-alpine

COPY nginx-selfsigned.crt /etc/nginx/ssl/nginx-selfsigned.crt
COPY nginx-selfsigned.key /etc/nginx/ssl/nginx-selfsigned.key

COPY nginx.conf  /etc/nginx/
COPY templates /etc/nginx/templates/
RUN echo "upstream php-upstream { server php:9000; }" > /etc/nginx/conf.d/upstream.conf

EXPOSE 80
EXPOSE 443
