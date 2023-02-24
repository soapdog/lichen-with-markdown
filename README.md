# THIS REPOSITORY MOVED

This project has new maintainers who are doing amazing work moving it further than what I originally envisioned. Give them your support and grab the fresh goodies from this project's new home at https://codeberg.org/ukrudt.net/lichen-markdown

# lichen-with-markdown

A fork of Lichen CMS that supports Markdown and Gemtext.

Find the original Lichen CMS alongside its documentation at:

https://lichen.sensorstation.co/

# License

The original Lichen and this fork are both licensed using MIT License.

# Development

I've been toying with this using the PHP built-in development server:

```
$ php81 -S localhost:8000
```

# Apache

With an apache web server, use the .htaccess in this repository (note that this htaccess file includes settings for .gmi and .md files)

# Nginx

With an nginx web server, use the nginx config in this repository in docs/nginx.conf (which also includes settings for .gmi and .md files)

# Docker 

The Dockerfile in docker/Dockerfile builds a docker image which can be used to serve lichen-with-markdown with apache,
via something like this:
```
docker build -t lichen:latest .
docker run -d -p 80:80 -v $(pwd)/lichen-with-markdown:/var/www/html lichen:latest
```

