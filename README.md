The ***parishcouncils*** system enables a group of Parish Councils (or an umbrella organisation providing Councils with common services) to join forces to deliver individualised facilities to meet their statutory obligations.

The motives for the development are described in ***parishcouncil***, an earlier iteration of the concept (see https://github.com/mjoycemilburn/parishcouncil). This system was aimed at individual councils that wanted a lightweight design that minimised the tasks of system-implementation. By contrast, the new ***parishcouncils*** system (plural) :
1. uses a more reliable technical platform (a server-based database rather than local files) and
2. enables participating councils to take advantage of the economic and administrative benefits of working cooperatively.

In this version of the system, individual councils still have their own url (and are thus indexed by search engines and have their own family of email addresses). However these urls are purchased as "additional urls" to a master url managed by the organising body, thereby minimising both cost and administrative inconvenience.

The organising body has the following responsibilities:

1. Opening an ISP account and acquiring the initial master url
2. Installing the **parishcouncils** system (see INSTALLATION_GUIDE)
3. Purchasing "additional urls" for each participating council (significantly cheaper than the cost of the parent ISP account) and adding a corresponding  sub-folder to the initial master url on the ISP
4.  Creating and maintaining user_id/password rights to protect each council's use of the maintenance system.

Once the system has been configured, each council will have its own independent website and will have accss to a simple tool to individualise this and manage its content into the future.

A sample website produced by the framework can be seen at https://ngatesystems.com/parishcouncils/council_a/.  A description of the configuration and maintenance tool can be seen at https://mjoycemilburn.github.io/parishcouncils/. 