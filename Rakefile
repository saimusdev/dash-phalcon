# Finding files
require 'find'

# HTML parsing
require 'nokogiri'

# DB connection
require 'sqlite3'

# Extraction of '.tar.gz' files
require 'rubygems/package'
require 'zlib'

task :default => [
  :create_docset,
  #:download_docs,
  :extract_docs,
  :create_plist,
  :create_db,
  :clean_docs,
  :parse_docs,
  #:import_docset,
  #:clean
]

# Docset specific variables
DOMAIN = "phalconphp.com"
DOCS_FOLDER = "docs." << DOMAIN
DOCSET_NAME = "Phalcon"
DOCSET_FOLDER = "#{DOCSET_NAME}.docset"
DOCSET_DOCS_SUBFOLDER = "#{DOCSET_FOLDER}/Contents/Resources/Documents"
PLIST_FILE = "#{DOCSET_FOLDER}/Contents/Info.plist"
DATABASE = "#{DOCSET_NAME}.docset/Contents/Resources/docSet.dsidx" 
LOCAL_DOCS_FOLDER = "phalcon_1.3.0_docs"
LOCAL_DOCS = LOCAL_DOCS_FOLDER + ".tar.gz"
ONLINE_DOCS = [
	"docs.phalconphp.com/en/latest/index.html"
]

UNNECESARY_HTML_NODES = [
	"/html/body/div[@id='wrapper']/div[@class='size-wrap']",
	"/html/body/div[@id='wrapper']/div[@class='header-line']",
	"/html/body/div[@id='wrapper']/div[@class='related']",
	"/html/body//td[@class='primary-box']",
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
	"/index.html",
	"*.js"
]
UPDATED_STYLES = [
	"body { min-width: 0px; } ", # Avoid horizontal scroll
	"td.second-box { padding: 0px 15px 10px 10px;} ", # Remove top-padding
]

	
task :create_docset do
    print_stdout "Creating folder structure..."
    if File.directory? DOCSET_FOLDER 
        FileUtils.rm_rf  DOCSET_FOLDER
    end
    FileUtils.mkdir_p DOCSET_DOCS_SUBFOLDER
    FileUtils.cp %w(icon.png icon@2x.png), "#{DOCSET_NAME}.docset/"
end

task :download_docs do
    print_stdout "Downloading the documentation for #{DOCSET_NAME}..."
    ONLINE_DOCS.each do |docs|
        print_stdout "Downloading from 'http://#{docs}'..."
        system "wget --recursive --page-requisites --adjust-extension --convert-links \
                --domains #{DOMAIN} --no-parent http://#{docs} 2>&1 | egrep -i '%|Saving to\'"
        FileUtils.mv docs, "#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}"
    end
end

task :extract_docs do
    print_stdout "Extracting the documentation for #{DOCSET_NAME}..."
    destination = Dir.getwd
    Gem::Package::TarReader.new( Zlib::GzipReader.open LOCAL_DOCS ) do |tar|
	  dest = nil
	  tar.each do |entry|
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
	FileUtils.mv LOCAL_DOCS_FOLDER, "#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}"
end

task :create_plist do
    print_stdout "Creating a Property List..."
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

task :create_db do
    print_stdout "Creating a Database to index documentation..."
   	db = SQLite3::Database.new DATABASE
   	db.execute "CREATE TABLE searchIndex(id INTEGER PRIMARY KEY, name TEXT, type TEXT, path TEXT);"
   	db.execute "CREATE UNIQUE INDEX anchor ON searchIndex (name, type, path);"
end


task :clean_docs do
 	print_stdout "Cleaning the documentation for better visualization..."
 	if File.directory? DOCSET_DOCS_SUBFOLDER
    	html_docs = get_html_docs(DOCSET_DOCS_SUBFOLDER + "/" + DOCS_FOLDER)
		html_docs.each do |doc_path|
			rewrite_html(doc_path)
		end
		update_css
	else
		print_stderr "Need to download the documentation before cleaning it!"
	end
end

task :parse_docs do
  print_stdout "Parsing the documentation for entries..."
  db = SQLite3::Database.new DATABASE
  find_guides "#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}/index.html"
end

task :import_docset do
  print_stdout "Importing the docset into Dash..."

end

task :clean do
  print_stdout "Cleaning..."
  if File.directory? DOCS_FOLDER
    FileUtils.rm_rf  DOCS_FOLDER
  end
  remove_unnecessaries
end

private
	EXCLUDED_FILES = ['.', '..', 'index.html', '.DS_Store']; 
	EXCLUDED_EXTENSIONS = ['txt','js','css','ico','png','svg','png','jpg'];

	CLASS = "Class"
	CONSTANT = "Constant"
	GUIDE = "Guide"
	METHOD = "Method"

	num_classes = 0
	num_constants = 0
	num_guides = 0
	num_methods = 0

	# Find all html documents
	def get_html_docs (path)
		relevant_doc_pages = []
		Find.find("#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}") do |path|
			if File.file?(path) && path =~ /.*html/
				relevant_doc_pages << path
	   		end
		end
		return relevant_doc_pages
	end

	# Remove unnecessary nodes and update some node attributes from html_file
	def rewrite_html (html_file)
		doc = Nokogiri::HTML(open(html_file))
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

	def find_guides (html_file)
		db = SQLite3::Database.new DATABASE
		doc = Nokogiri::HTML(open(html_file))
    	node_set = doc.xpath "/html/body//li[@class='toctree-l1']"
    	num_guides = node_set.length
    	print_stdout "Found " + num_guides.to_s + " guides"
    	node_set.each do |node|
    		# Avoid parsing the API index
    		next if node.xpath("a[@href='api/index.html']").length != 0

    		anchor = node.xpath("a")
    		guide_name = anchor.inner_html.to_s
    		guide_href = anchor.attribute("href").to_s
    		anchor.each { |a| a.add_previous_sibling("<a name='//apple_ref/cpp/#{GUIDE}/#{guide_name}' class='dashAnchor'></a>")}
    		insert_stmt = db.prepare "INSERT OR IGNORE INTO searchIndex (name, type, path) VALUES (?, ?, ?)"
    		insert_stmt.execute [ guide_name, GUIDE, guide_href ]
	 	end
	 	File.open(html_file,'w') { |file| doc.write_html_to file }
	end

	# Add updated styles to stylesheets
	def update_css ()
		Find.find("#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}") do |path|
			if File.file?(path) && path =~ /.*css/
				File.open(path,'a') do |file| 
					UPDATED_STYLES.each { |rule| file.puts rule<<"\n" }
	   			end
	   		end
		end
	end

	# Remove unnecessary files 
	def remove_unnecessaries ()
   		Find.find("#{DOCSET_DOCS_SUBFOLDER}/#{DOCS_FOLDER}") do |path|
   			if File.file? path
				UNNECESARY_FILES.each do |unnecessary_file|
	   				FileUtils.rm_rf path if path =~ /#{Regexp.escape(unnecessary_file)}/
				end
   			end
   		end
	end

	def print_stderr(text); print "\e[31m#{text}\e[0m\n"; end
	def print_stdout(text); print "\e[32m#{text}\e[0m\n"; end
