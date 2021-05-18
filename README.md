The **parishcouncils** system enables a "sponsor" - a group of Parish Councils (or an umbrella organisation providing Councils with common services) to enable participating Councils to create and maintain websites to meet their statutory obligations at minimal cost.

Why might a Council be interested in such a service? If you've ever been involved in developing a Parish Council website, you will already know the answer. If not, you can read a full description of the motives behind the development of this system in **parishcouncil**, an earlier iteration of the concept (see https://github.com/mjoycemilburn/parishcouncil). This early system was aimed at individual councils with minimal IT skills and went to great lengths to avoid the need to create and maintain a database. By contrast, the new **parishcouncils** system (plural) :
1. assumes that IT skills are available to permit the use of a server-based database (a much more reliable mode of operation, albeit more complex) and
2. enables participating councils to take advantage of the economic and administrative benefits of working cooperatively.

Although in **parishcouncils**, all participating Councils share a common database on a common host, individual councils still have their own url (and are thus indexed by search engines and have their own family of email addresses). These urls are purchased as "additional urls" to a master url managed by the sponsor, however, so that both cost and administrative inconvenience are minimised.

The sponsor has the following responsibilities:

1. Opening an ISP account and acquiring the initial master url
2. Installing the **parishcouncils** system (see INSTALLATION_GUIDE)
3. Purchasing "additional urls" for each participating council (significantly cheaper than the cost of the parent ISP account) and adding a corresponding  sub-folder to the initial master url on the ISP
4.  Creating and maintaining user_id/password rights to protect each council's use of the maintenance system.

Once the system has been installed in this way, each council will have its own independent website and will have access to a simple tool to individualise this and manage its content.

A sample website produced by the framework can be seen at https://milburnparishcouncil.co.uk/.  A description of the configuration and maintenance tool can be seen at https://mjoycemilburn.github.io/parishcouncils/. 