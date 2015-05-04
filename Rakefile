# Finding files
require 'find'

# Path checking
require 'pathname'

# HTML parsing
require 'nokogiri'

# DB connection
require 'sqlite3'

# Extraction of '.tar.gz' files
require 'rubygems/package'
require 'zlib'


# Tasks to run by default
task :all => :default
task :default => [
  :create,
  #:download,
  :extract,
  :plist,
  :db,
  :clean,
  :parse,
  :import,
  :rm
]

# Docset specific variables
DOMAIN = "phalconphp.com"
DOCS_FOLDER = "docs." << DOMAIN
DOCSET_NAME = "Phalcon 1.3.0"
DOCSET_FOLDER = "#{DOCSET_NAME}.docset"
DOCSET_DOCS_SUBFOLDER = "#{DOCSET_FOLDER}/Contents/Resources/Documents"
PLIST_FILE = "#{DOCSET_FOLDER}/Contents/Info.plist"
DATABASE = "#{DOCSET_NAME}.docset/Contents/Resources/docSet.dsidx" 
LOCAL_DOCS_FOLDER = "phalcon_1.3.0_docs"
LOCAL_DOCS = LOCAL_DOCS_FOLDER + ".tar.gz"
ONLINE_DOCS = [
	"docs.phalconphp.com/en/latest/index.html"
]
DOCSET_PATH = Pathname.new "#{DOCSET_DOCS_SUBFOLDER}"

UNNECESARY_HTML_NODES = [
	"/html/body/div[@id='wrapper']/div[@class='size-wrap']",
	"/html/body/div[@id='wrapper']/div[@class='header-line']",
	"/html/body/div[@id='wrapper']/div[@class='related']",
	"/html/body//td[@class='primary-box']",
	"/html/body//td[@class='second-box']//div[@id='table-of-contents']",
	"/html/body//td[@class='second-box']//div[@id='other-formats']",	
	"/html/body/div[@id='wrapper']/div[@id='footer']",
	"/html/head//script",
	"/html/body//script"
]
UPDATED_HTML_NODES = [
	[ 
		"/html/body/div[@id='wrapper']/table[@width='90%']", # Node
		"width", # Attribute name
		"100%", # New attribute  value
	]
]
UNNECESARY_FILES = [
	"/_sources",
	"/genindex.html",
	"/genindex-all.html",
	"*.js"
]
UPDATED_STYLES = [
	"body { min-width: 0px; } ", # Avoid horizontal scroll
	"td.second-box { padding: 0px 15px 10px 10px;} ", # Remove top-padding
]
EXCLUDED_FILES = ['.', '..', 'index.html', '.DS_Store']; 
EXCLUDED_EXTENSIONS = ['txt','js','css','ico','png','svg','png','jpg'];

CLASS = "Class"
CONSTANT = "Constant"
GUIDE = "Guide"
METHOD = "Method"

$num_classes = 0
$num_constants = 0
$num_guides = 0
$num_methods = 0
$database = nil 

##############################################################################################
# Create the docset folder structure
task :create do
    print_task "Creating folder structure..."
    if File.directory? DOCSET_FOLDER 
        FileUtils.rm_rf  DOCSET_FOLDER
    end
    FileUtils.mkdir_p "#{DOCSET_FOLDER}/Contents/Resources"
    FileUtils.cp %w(icon.png icon@2x.png), "#{DOCSET_NAME}.docset/"
end

##############################################################################################
# Download the documentation 
task :download do
    print_task "Downloading the documentation for #{DOCSET_NAME}..."
    ONLINE_DOCS.each do |docs|
        print_task "Downloading from 'http://#{docs}'..."
        system "wget --recursive --page-requisites --adjust-extension --convert-links \
                --domains #{DOMAIN} --no-parent http://#{docs} 2>&1 | egrep -i '%|Saving to\'"
        FileUtils.mv docs, DOCSET_PATH
    end
end

##############################################################################################
# Extract the locally stored documentation '.tar.gz' file
task :extract do
    print_task "Extracting the documentation for #{DOCSET_NAME}..."
    destination = Dir.getwd
    Gem::Package::TarReader.new( Zlib::GzipReader.open LOCAL_DOCS ) do |tar|
	  dest = nil
	  tar.each do |entry|
	  	print_progress "extracting", entry.full_name
	    if entry.full_name == '././@LongLink'
	      dest = File.join destination, entry.read.strip
	      next
	    end
	    dest ||= File.join destination, entry.full_name
	    if entry.directory?
	      FileUtils.rm_rf dest unless File.directory? dest
	      FileUtils.mkdir_p dest, :mode => entry.header.mode, :verbose => false
	    elsif entry.file?
	      FileUtils.rm_rf dest unless File.file? dest
	      File.open dest, "wb" do |f|
	        f.print entry.read
	      end
	      FileUtils.chmod entry.header.mode, dest, :verbose => false
	    elsif entry.header.typeflag == '2' #Symlink!
	      File.symlink entry.header.linkname, dest
	    end
	    dest = nil
	  end
	end
	FileUtils.mv LOCAL_DOCS_FOLDER, DOCSET_PATH
end

##############################################################################################
# Create the Property List file
task :plist do
    print_task "Creating a Property List..."
    tab_space = ""
    4.times { tab_space <<= " " }

    plist_file = File.new(PLIST_FILE, 'w')
    plist_file.puts "<?xml version='1.0' encoding='UTF-8'?>\n" \
        << "<!DOCTYPE plist PUBLIC '-//Apple//DTD PLIST 1.0//EN' 'http://www.apple.com/DTDs/PropertyList-1.0.dtd'>\n" \
        << "<plist version='1.0'>\n" \
        << "<dict>\n" \
        << tab_space << "<key>CFBundleIdentifier</key>\n" \
        << tab_space << "<string>#{DOCSET_NAME}</string>\n" \
        << tab_space << "<key>CFBundleName</key>\n" \
        << tab_space << "<string>#{DOCSET_NAME}</string>\n" \
        << tab_space << "<key>DocSetPlatformFamily</key>\n" \
        << tab_space << "<string>#{DOCSET_NAME}</string>\n" \
        << tab_space << "<key>isDashDocset</key>\n" \
        << tab_space << "<true/>\n" \
        << tab_space << "<key>dashIndexFilePath</key>\n" \
        << tab_space << "<string>index.html</string>\n" \
        << tab_space << "<key>DashDocSetFamily</key>\n" \
        << tab_space << "<string>dashtoc</string>\n" \
        << tab_space << "<key>isJavaScriptEnabled</key>\n" \
        << tab_space << "<false/>\n" \
        << "</dict>\n" \
        << "</plist>\n"
    plist_file.close
end

##############################################################################################
# Create the database, table and Index
task :db do
    print_task "Creating a Database to index documentation..."
   	$database = SQLite3::Database.new DATABASE
   	$database.execute "CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT);"
   	$database.execute "CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path);"
end

##############################################################################################
# Clean the documentation files
task :clean do
 	print_task "Cleaning the documentation for better visualization..."
 	if File.directory? DOCSET_DOCS_SUBFOLDER
    	html_docs = get_html_docs(DOCSET_DOCS_SUBFOLDER + "/" + DOCS_FOLDER)
		html_docs.each do |doc_path|
			rewrite_html(doc_path)
		end
		update_css
	else
		print_error "Need to download the documentation before cleaning it!"
	end
end

##############################################################################################
# Search the documentation for keywords
task :parse do
  print_task "Parsing the documentation for entries..."
  $database = SQLite3::Database.new DATABASE
  if File.directory? DOCSET_DOCS_SUBFOLDER
  		print_task "Looking for guides..."
  		find_guides DOCSET_PATH.to_s + "/index.html"
    	html_docs = get_html_docs(DOCSET_DOCS_SUBFOLDER + "/" + DOCS_FOLDER)
		print_task "Looking for classes, methods and constants..."
		html_docs.each do |doc_path|
			find_other doc_path
		end
		print_task "Found " + $num_guides.to_s + " guides"
		print_task "Found " + $num_classes.to_s + " classes"
		print_task "Found " + $num_constants.to_s + " constants"
		print_task "Found " + $num_methods.to_s + " methods"
	else
		print_error "Need to download the documentation before parsing!"
	end
end

##############################################################################################
# Import the docset into Dash
task :import do
  print_task "Importing the docset into Dash..."
  DASH_DOCSETS_LOCATION = "#{ENV['HOME']}/Library/Application Support/Dash/User Contributed"
  DASH_APP = "/Applications/Dash.app"
  if File.directory? DASH_DOCSETS_LOCATION
	FileUtils.mkdir_p "#{DASH_DOCSETS_LOCATION}/#{DOCSET_NAME}" if !File.directory? "#{DASH_DOCSETS_LOCATION}/#{DOCSET_NAME}"
  	FileUtils.cp_r DOCSET_FOLDER, "#{DASH_DOCSETS_LOCATION}/#{DOCSET_NAME}/#{DOCSET_FOLDER}"
  	if File.directory? DASH_APP
		system "open -a \'#{DASH_APP}\' \'#{DASH_DOCSETS_LOCATION}/#{DOCSET_NAME}/#{DOCSET_FOLDER}\'"
  	else
  		print_error "Dash is not installed"
  	end
  else
	print_error "Directory #{DASH_DOCSETS_LOCATION} does not exist!"
  end
end

##############################################################################################
# Delete all temporary files
task :rm do
  print_task "Cleaning..."
  FileUtils.rm_rf(DOCS_FOLDER) if File.directory? DOCS_FOLDER
  if File.directory? DOCSET_FOLDER
  	#FileUtils.rm_rf(DOCSET_FOLDER) if File.directory? DOCSET_FOLDER
  	remove_unnecessaries
  end
end

##############################################################################################
# Private auxiliary functions
private
	# Find all html documents
	def get_html_docs (path)
		relevant_doc_pages = []
		Find.find(DOCSET_PATH) do |path|
			if File.file?(path) && path =~ /.*html/
				relevant_doc_pages << path
	   		end
		end
		return relevant_doc_pages
	end

	# Remove unnecessary nodes and update some node attributes from html_file
	def rewrite_html (html_file)
		doc = Nokogiri::HTML(open(html_file))
		print_progress "cleaning", File.basename(html_file)
		UNNECESARY_HTML_NODES.each do |node_path|
        	node = doc.xpath node_path
        	node.remove
   		end
   		UPDATED_HTML_NODES.each do |node_path|
        	node = doc.xpath node_path[0]
        	node.set node_path[1], node_path[2]
   		end
   		File.open(html_file,'w') { |file| doc.write_html_to file }
	end 

	# Find all guide references in file 'html_file'
	def find_guides (html_file)
		doc = Nokogiri::HTML(open(html_file))
		print_progress "parsing", File.basename(html_file)
    	guides_nodes = doc.xpath "/html/body//li[@class='toctree-l1']/a[@class='reference internal']"
    	$num_guides += guides_nodes.length
    	guides_nodes.each do |guide|
    		guide_href = guide.attribute("href").to_s
    		next if guide_href == "api/index.html" # avoid parsing the API index
    		guide_name = guide.inner_html.to_s
    		insert guide, guide_name, GUIDE, guide_href
	 	end
	 	File.open(html_file,'w') { |file| doc.write_html_to file }
	end

	# Find all class, constant and method references in file 'html_file'
	def find_other (html_file)
		doc = Nokogiri::HTML(open(html_file))
		print_progress "parsing", File.basename(html_file)
    	doc.xpath("/html/body//h1/a").remove
    	class_nodes = doc.xpath "/html/body//h1/strong"
    	constants_nodes = doc.xpath "/html/body//div[@id='constants']/p/strong"
    	methods_nodes = doc.xpath "/html/body//div[@id='methods']/p/strong"

    	if class_nodes.length >= 0
    		$num_classes += search_nodeset html_file, CLASS, class_nodes
    	end
    	if constants_nodes.length >= 0
    		$num_constants += search_nodeset html_file, CONSTANT, constants_nodes
    	end
    	if methods_nodes.length >= 0
    		$num_methods += search_nodeset html_file, METHOD, methods_nodes
    	end
	 	File.open(html_file,'w') { |file| doc.write_html_to file }
	end

	# Search in a node set for a node type
	def search_nodeset(file_name, node_type, node_set)
		node_number = 0
		node_set.each do |node| 
			node_name = node.inner_html.to_s
			insert node, node_name, node_type, Pathname.new(file_name).relative_path_from(DOCSET_PATH).to_s
    		node_number += 1
		end
		return node_number
	end

	# Add dash-link and insert to database
	def insert (node, node_name, node_type, node_href)
		node.add_previous_sibling("<a name='//apple_ref/cpp/#{node_type}/#{node_name}' id='#{node_name}' class='dashAnchor'></a>")
		insert_stmt = $database.prepare "INSERT OR IGNORE INTO searchIndex (name, type, path) VALUES (?, ?, ?)"
    	insert_stmt.execute [ node_name, node_type, node_href + "#" + node_name ]
    	#print_progress "added #{node_type}: ", node_name
	end

	# Add updated styles to stylesheets
	def update_css ()
		Find.find(DOCSET_PATH) do |path|
			if File.file?(path) && path =~ /.*css/
				File.open(path,'a') do |file| 
					UPDATED_STYLES.each { |rule| file.puts rule<<"\n" }
	   			end
	   		end
		end
	end

	# Remove unnecessary files 
	def remove_unnecessaries ()
   		Find.find(DOCSET_PATH) do |path|
   			if File.file? path
				UNNECESARY_FILES.each do |unnecessary_file|
	   				FileUtils.rm_rf path if path =~ /#{Regexp.escape(unnecessary_file)}/
				end
   			end
   		end
	end

	def print_error(text); $stderr.print "\e[31m#{text}\e[0m\n"; end
	def print_task(text); print "\e[32m#{text}\e[0m\n"; end
	def print_progress(text,keyword=""); print "#{text} \e[33m#{keyword}\e[0m\n"; end

