LongLive HTTP Monitoring API & CLI Toolset
============================

LongLive helps you monitor your HTTP applications availablity, and gives you couple of endpoints to publicly share your servers uptime and response time statistics. (if you choose to do so!). 

What's inside?
--------------
 - LongLive HTTP Monitoring API is based on Symfony3.0.
 - Uses Mysql as database, 
 - Works with PHP >=5.5.9,

Backstory
-----------
This project was born out of bare necessity. I could'nt find any local tools to create performance and uptime statics then... Indeed, there are good services like [pingdom](https://www.pingdom.com/) but my need was minimizing the false-negatives that occur with cross-border requests. (you can't belive how often it happens.). Anyway, this project was the result of it, after working on my server for two and a half years, i think it is the time to publish it.

What is it exactly?
-------------------
It is a mix of command line tools and couple of http endpoints that checks your http services with the rules that you specify, and records the performance stats to your database for publishing, or just for your eyes only. Also, it sends e-mails to predefined addresses if it can not reach to the service you set up.

How it Works?
-------------
1. Clone the repo
2. ```cd LongLive```
3. run ```composer install```
4. follow the composer prompts.
5. create the database with: 
    ```bin/console doctrine:database:create```
6. create the schema with: 
    ```bin/console doctrine:schema:create```

Now you are ready to go! As an example, let's create a rule to check if google.com is up;
```bin/console status:check --add```
This command will start a prompt series for you to answer couple of simple questions to get you up and running in no time.
```
Mandatory Please enter a name for your rule (alphanumeric) : GOOGLE
Mandatory Please enter the URL of the service you want to check : https://www.google.com
Optional Please enter the Port of the service you want to check (default 80) : 80
Optional  Enter a string to check in the response :
Optional  Timeout in milliseconds (10000) : 5000
+-----------+------------------------+------+-------------------------+---------+
| Rule Name | URL                    | PORT | Response Should Include | Timeout |
+-----------+------------------------+------+-------------------------+---------+
| GOOGLE    | https://www.google.com | 80   |                         | 5000    |
+-----------+------------------------+------+-------------------------+---------+
Do you confirm that you want to add this rule to database? (yes/no) y
Rule GOOGLE added succesfully. You can use it now..
```

As you can see, your rule is set, and ready to go. On order to check it you can use the following command;
```bin/console status:check GOOGLE```

You should get something like this as a response;
```
+--------+-------------+----------------+-------------+---------------------+
| RuleID | Status Code | Check For Clue | Clue Found? | Total Response Time |
+--------+-------------+----------------+-------------+---------------------+
| 1      | 200         |                |             | 0.202212            |
+--------+-------------+----------------+-------------+---------------------+
```

But who needs a command line tool for manually calling couple of predefined rules? What good is it?
Ok, the answer is simple, you can add more rules to your database, and then, you can call all of them like this;
```bin/console status:check --all```

For all the available options and capabilities, you can check;
```bin/console status:check --help```
This is the result;
```
Usage:
  status:check [options] [--] [<ruleName>]

Arguments:
  ruleName              Which rule you want to run?

Options:
  -a, --add             Add A new rule with step by step wizard.
  -r, --remove          Remove a rule by name
  -A, --all             If set, all rules will be run one after another.
  -l, --list            Lists all the rules.
  -g, --generate        Generate test data for every entry on rule database (do not use in production)
  -i, --info            Gives information about the bundle.
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -e, --env=ENV         The Environment name. [default: "dev"]
      --no-debug        Switches off debug mode.
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Help:
 Main command to be called with cron. If "all" option is not set, you have to supply a ruleName to run.
```

In order to use it with its full potential, give it a couple of rules, set a cron on the server that calls the command every minute with ```--all``` parameter. It will keep working as long as the server and network is up.

Also, if you set your apache or nginx to work with LongLive HTTP API, you will have the following HTTP endpoints to read the data that the system records;

yourdomain.com/logs
yourdomain.com/day

__TODO:__ improve documentation on http endpoints.
