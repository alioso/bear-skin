group :development do
  guard 'livereload' do
    watch(%r{.+\.(css|js|html|php|png|jpg|gif?)$})
  end

  guard :compass, configuration_file: 'config.rb',
        compile_on_start: true do
    watch(%r{..\/assets\/sass\/.*\.scss$})
  end
end