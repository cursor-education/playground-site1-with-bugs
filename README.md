## Setup

> ### on local:
> ```bash
> $ boot2docker up
> $ make dev
> $ open http://$(boot2docker ip):8080/
> ```
> 
> ### on stage:
> ```bash
> $ curl -sSL https://get.docker.com/ | sh
> $ docker -v
> $ sudo service docker restart
> $ git clone https://github.com/... site/
> $ cd site/ && make PORT=80
> $ open http://site/
> ```