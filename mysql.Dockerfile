FROM mariadb:11.4.2

# Set the timezone
ENV TZ=America/Denver

# Install tzdata to apply the timezone setting
RUN apt-get update && apt-get install -y tzdata

# Set the timezone in the operating system
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone