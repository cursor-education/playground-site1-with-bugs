env:
	mkdir web/components || true
	chmod -R 0777 web/components
	bower install --allow-root --config.interactive=false

server:
	killall node || true
	nohup http-server ${home}/web -a 0.0.0.0 >/dev/null 2>/dev/null &

.PHONY: env server